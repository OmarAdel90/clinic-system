<?php

namespace Modules\CRM\Services;

use Modules\CRM\Models\Conversation;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Message;
use Modules\CRM\Models\WebhookLog;
use Modules\Lead\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function verifySignature(string $rawBody, string $signatureHeader): bool
    {
        $secret = config('services.meta_app_secret');
        if (!$secret) {
            Log::warning('META_APP_SECRET is not configured — skipping signature verification.');
            return true;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }

    public function verifyToken(string $token): bool
    {
        $validTokens = array_filter([
            config('services.meta_whatsapp.verify_token'),
            config('services.meta_facebook.verify_token'),
        ], fn ($value) => filled($value));

        return in_array($token, $validTokens, true);
    }

    public function process(array $payload, array $headers): void
    {
        $object = $payload['object'] ?? null;
        $entries = $payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $this->processEntry($object, $entry);
        }

        WebhookLog::create([
            'source'       => $object,
            'event_type'   => $this->resolveEventType($object, $entries),
            'payload'      => $payload,
            'headers'      => $headers,
            'processed_at' => now(),
        ]);
    }

    protected function processEntry(?string $object, array $entry): void
    {
        match ($object) {
            'whatsapp_business_account' => $this->processWhatsAppEntry($entry),
            'page'                      => $this->processFacebookEntry($entry),
            'instagram'                 => $this->processInstagramEntry($entry),
            default                     => Log::warning('Unknown webhook object', ['object' => $object]),
        };
    }

    protected function processWhatsAppEntry(array $entry): void
    {
        $changes = $entry['changes'] ?? [];

        foreach ($changes as $change) {
            $value = $change['value'] ?? [];

            foreach ($value['messages'] ?? [] as $msgData) {
                $this->handleWhatsAppMessage($value, $msgData);
            }

            foreach ($value['statuses'] ?? [] as $statusData) {
                $this->handleWhatsAppStatus($statusData);
            }
        }
    }

    protected function handleWhatsAppMessage(array $value, array $msgData): void
    {
        $contacts = $value['contacts'] ?? [];
        $contact = $contacts[0] ?? [];
        $waId = $contact['wa_id'] ?? $msgData['from'] ?? null;
        $profileName = $contact['profile']['name'] ?? null;
        $phone = $msgData['from'] ?? $waId;
        $messageId = $msgData['id'] ?? null;
        $timestamp = $msgData['timestamp'] ?? now()->timestamp;
        $type = $msgData['type'] ?? 'unknown';
        $metaCampaignId = $this->extractMetaCampaignId($value);

        if (!$waId) {
            Log::warning('WhatsApp webhook missing wa_id', ['msg' => $msgData]);
            return;
        }

        if ($messageId && Message::where('wa_message_id', $messageId)->exists()) {
            return;
        }

        $lead = $this->resolveWhatsAppLead($waId, $phone);

        if (! $lead) {
            $lead = Lead::create([
                'whatsapp_id'  => $waId,
                'platform'     => 'whatsapp',
                'phone'        => $phone,
                'name'         => $profileName,
                'profile_name' => $profileName,
                'campaign_id'  => $this->resolveCampaignId($metaCampaignId),
                'metadata'     => $this->buildLeadMetadata('whatsapp_webhook', $metaCampaignId, $value),
            ]);
        }

        $leadUpdates = [
            'whatsapp_id' => $lead->whatsapp_id ?: $waId,
            'phone' => $lead->phone ?: $phone,
            'platform' => $lead->platform ?: 'whatsapp',
            'campaign_id' => $lead->campaign_id ?: $this->resolveCampaignId($metaCampaignId),
            'metadata' => $this->mergeLeadMetadata($lead->metadata, $this->buildLeadMetadata('whatsapp_webhook', $metaCampaignId, $value)),
        ];

        if ($profileName) {
            $leadUpdates['profile_name'] = $profileName;

            if (blank($lead->name)) {
                $leadUpdates['name'] = $profileName;
            }
        }

        if ($this->hasDirtyValues($lead, $leadUpdates)) {
            $lead->update($leadUpdates);
        }

        $conversation = Conversation::firstOrCreate(
            ['lead_id' => $lead->id, 'platform' => 'whatsapp'],
            [
                'first_message_time' => now(),
                'status'             => 'active',
                'lead_status'        => 'new',
            ]
        );

        $body = $this->extractMessageBody($type, $msgData);
        $media = $this->extractMedia($type, $msgData);
        $media = $this->hydrateWhatsAppInboundMedia($media, $msgData);

        Message::create([
            'conversation_id'  => $conversation->id,
            'lead_id'          => $lead->id,
            'wa_message_id'    => $messageId,
            'direction'        => 'inbound',
            'type'             => $type,
            'body'             => $body,
            'media_url'        => $media['url'] ?? null,
            'media_caption'    => $media['caption'] ?? null,
            'media_mime'       => $media['mime'] ?? null,
            'media_size'       => $media['size'] ?? null,
            'payload'          => $msgData,
            'status'           => 'received',
            'sent_at'          => now()->setTimestamp((int) $timestamp),
        ]);

        $conversation->update([
            'last_message_time' => now(),
            'unread_amount'     => DB::raw('unread_amount + 1'),
        ]);
    }

    protected function resolveWhatsAppLead(string $waId, ?string $phone): ?Lead
    {
        $phoneCandidates = array_values(array_unique(array_filter([
            $phone,
            $waId,
            $phone ? '+' . ltrim($phone, '+') : null,
            '+' . ltrim($waId, '+'),
        ])));

        return Lead::query()
            ->where('whatsapp_id', $waId)
            ->orWhereIn('phone', $phoneCandidates)
            ->first();
    }

    protected function hasDirtyValues(Lead $lead, array $updates): bool
    {
        foreach ($updates as $key => $value) {
            if ($lead->{$key} !== $value) {
                return true;
            }
        }

        return false;
    }

    protected function handleWhatsAppStatus(array $statusData): void
    {
        $waMessageId = $statusData['id'] ?? null;
        $status = $statusData['status'] ?? null;
        $timestamp = $statusData['timestamp'] ?? null;

        if (!$waMessageId || !$status) return;

        $message = Message::where('wa_message_id', $waMessageId)->first();
        if (!$message) return;

        $updates = match ($status) {
            'sent'      => ['status' => 'sent', 'sent_at' => now()->setTimestamp((int) $timestamp)],
            'delivered' => ['status' => 'delivered', 'delivered_at' => now()->setTimestamp((int) $timestamp)],
            'read'      => ['status' => 'read', 'read_at' => now()->setTimestamp((int) $timestamp)],
            'failed'    => ['status' => 'failed', 'failed_at' => now(), 'error_message' => $statusData['errors'][0]['message'] ?? null],
            default     => ['status' => $status],
        };

        $message->update($updates);
    }

    protected function processFacebookEntry(array $entry): void
    {
        $messaging = $entry['messaging'] ?? [];

        foreach ($messaging as $event) {
            $this->handleFacebookMessage($event);
        }
    }

    protected function handleFacebookMessage(array $event): void
    {
        $sender = $event['sender'] ?? [];
        $message = $event['message'] ?? [];
        $psid = $sender['id'] ?? null;
        $timestamp = $event['timestamp'] ?? now()->timestamp;
        $mid = $message['mid'] ?? null;
        $text = $message['text'] ?? null;
        $attachments = $message['attachments'] ?? [];
        $metaCampaignId = $this->extractMetaCampaignId($event);

        if (!$psid || (!$text && empty($attachments))) return;

        $lead = Lead::firstOrCreate(
            ['whatsapp_id' => $psid, 'platform' => 'facebook'],
            [
                'phone'    => null,
                'name'     => null,
                'campaign_id' => $this->resolveCampaignId($metaCampaignId),
                'platform' => 'facebook',
                'metadata' => $this->buildLeadMetadata('facebook_webhook', $metaCampaignId, $event),
            ]
        );

        if (! $lead->wasRecentlyCreated && ($metaCampaignId || data_get($event, 'referral'))) {
            $lead->update([
                'campaign_id' => $lead->campaign_id ?: $this->resolveCampaignId($metaCampaignId),
                'metadata' => $this->mergeLeadMetadata($lead->metadata, $this->buildLeadMetadata('facebook_webhook', $metaCampaignId, $event)),
            ]);
        }

        $conversation = Conversation::firstOrCreate(
            ['lead_id' => $lead->id, 'platform' => 'facebook'],
            [
                'first_message_time' => now(),
                'status'             => 'active',
                'lead_status'        => 'new',
            ]
        );

        $msgType = 'text';
        $body = $text;
        $media = null;

        if (!empty($attachments)) {
            $attachment = $attachments[0];
            $msgType = $attachment['type'] ?? 'file';
            $media = [
                'url'     => $attachment['payload']['url'] ?? null,
                'mime'    => null,
                'caption' => $text,
                'size'    => null,
            ];
            if (!$body) $body = "[$msgType]";
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'lead_id'         => $lead->id,
            'direction'       => 'inbound',
            'type'            => $msgType,
            'body'            => $body,
            'media_url'       => $media['url'] ?? null,
            'media_caption'   => $media['caption'] ?? null,
            'payload'         => $event,
            'status'          => 'received',
            'sent_at'         => now()->setTimestamp((int) $timestamp),
        ]);

        $conversation->update([
            'last_message_time' => now(),
            'unread_amount'     => DB::raw('unread_amount + 1'),
        ]);
    }

    protected function processInstagramEntry(array $entry): void
    {
        $messaging = $entry['messaging'] ?? [];

        foreach ($messaging as $event) {
            $this->handleInstagramMessage($event);
        }
    }

    protected function handleInstagramMessage(array $event): void
    {
        $sender = $event['sender'] ?? [];
        $message = $event['message'] ?? [];
        $igid = $sender['id'] ?? null;
        $timestamp = $event['timestamp'] ?? now()->timestamp;
        $mid = $message['mid'] ?? null;
        $text = $message['text'] ?? null;
        $attachments = $message['attachments'] ?? [];
        $metaCampaignId = $this->extractMetaCampaignId($event);

        if (!$igid || (!$text && empty($attachments))) return;

        $lead = Lead::firstOrCreate(
            ['whatsapp_id' => $igid, 'platform' => 'instagram'],
            [
                'phone'    => null,
                'name'     => null,
                'campaign_id' => $this->resolveCampaignId($metaCampaignId),
                'platform' => 'instagram',
                'metadata' => $this->buildLeadMetadata('instagram_webhook', $metaCampaignId, $event),
            ]
        );

        if (! $lead->wasRecentlyCreated && ($metaCampaignId || data_get($event, 'referral'))) {
            $lead->update([
                'campaign_id' => $lead->campaign_id ?: $this->resolveCampaignId($metaCampaignId),
                'metadata' => $this->mergeLeadMetadata($lead->metadata, $this->buildLeadMetadata('instagram_webhook', $metaCampaignId, $event)),
            ]);
        }

        $conversation = Conversation::firstOrCreate(
            ['lead_id' => $lead->id, 'platform' => 'instagram'],
            [
                'first_message_time' => now(),
                'status'             => 'active',
                'lead_status'        => 'new',
            ]
        );

        $msgType = 'text';
        $body = $text;
        $media = null;

        if (!empty($attachments)) {
            $attachment = $attachments[0];
            $msgType = $attachment['type'] ?? 'file';
            $media = [
                'url'     => $attachment['payload']['url'] ?? null,
                'mime'    => null,
                'caption' => $text,
                'size'    => null,
            ];
            if (!$body) $body = "[$msgType]";
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'lead_id'         => $lead->id,
            'direction'       => 'inbound',
            'type'            => $msgType,
            'body'            => $body,
            'media_url'       => $media['url'] ?? null,
            'media_caption'   => $media['caption'] ?? null,
            'payload'         => $event,
            'status'          => 'received',
            'sent_at'         => now()->setTimestamp((int) $timestamp),
        ]);

        $conversation->update([
            'last_message_time' => now(),
            'unread_amount'     => DB::raw('unread_amount + 1'),
        ]);
    }

    protected function extractMessageBody(string $type, array $msgData): ?string
    {
        return match ($type) {
            'text'        => $msgData['text']['body'] ?? null,
            'interactive' => $msgData['interactive']['button_reply']['title']
                ?? $msgData['interactive']['list_reply']['title']
                ?? null,
            'button'      => $msgData['button']['text'] ?? null,
            'order'       => $msgData['order']['catalog_id'] ?? null,
            'system'      => $msgData['system']['body'] ?? null,
            default       => null,
        };
    }

    protected function extractMedia(string $type, array $msgData): ?array
    {
        if (in_array($type, ['image', 'audio', 'video', 'document', 'sticker'], true)) {
            $media = $msgData[$type] ?? [];

            return [
                'id'      => $media['id'] ?? null,
                'url'     => null,
                'caption' => $media['caption'] ?? null,
                'mime'    => $media['mime_type'] ?? null,
                'size'    => $media['sha256'] ?? null,
            ];
        }

        return null;
    }

    protected function hydrateWhatsAppInboundMedia(?array $media, array $msgData): ?array
    {
        if (! is_array($media) || blank($media['id'] ?? null)) {
            return $media;
        }

        try {
            $downloaded = app(MetaWhatsAppService::class)->downloadMedia(
                (string) $media['id'],
                $msgData['document']['filename'] ?? null,
            );

            if (! $downloaded) {
                return $media;
            }

            $media['url'] = url($downloaded['path']);
            $media['mime'] = $downloaded['mime_type'] ?? $media['mime'];
        } catch (\Throwable $e) {
            Log::warning('Unable to download inbound WhatsApp media.', [
                'media_id' => $media['id'],
                'error' => $e->getMessage(),
            ]);
        }

        return $media;
    }

    protected function resolveEventType(?string $object, array $entries): string
    {
        if ($object === 'whatsapp_business_account') {
            $changes = $entries[0]['changes'] ?? [];
            $value = $changes[0]['value'] ?? [];

            if (!empty($value['messages'])) return 'message';
            if (!empty($value['statuses'])) return 'status';
        }

        if (in_array($object, ['page', 'instagram'], true)) {
            $messaging = $entries[0]['messaging'] ?? [];
            if (!empty($messaging)) return 'message';
        }

        return 'unknown';
    }

    protected function resolveCampaignId(?string $metaCampaignId): ?int
    {
        if (blank($metaCampaignId)) {
            return null;
        }

        return Campaign::query()->whereKey((int) $metaCampaignId)->exists()
            ? (int) $metaCampaignId
            : null;
    }

    protected function buildLeadMetadata(string $source, ?string $metaCampaignId, array $payload): array
    {
        return array_filter([
            'source' => $source,
            'meta_campaign_id' => $metaCampaignId,
            'meta_ad_id' => $this->extractNestedValue($payload, 'ad_id'),
            'meta_adset_id' => $this->extractNestedValue($payload, 'adset_id'),
        ], fn ($value) => filled($value));
    }

    protected function mergeLeadMetadata(?array $current, array $incoming): array
    {
        return array_filter(array_merge($current ?? [], $incoming), fn ($value) => $value !== null && $value !== '');
    }

    protected function extractMetaCampaignId(array $payload): ?string
    {
        $candidate = $this->extractNestedValue($payload, 'campaign_id');

        if (filled($candidate)) {
            return (string) $candidate;
        }

        $referral = data_get($payload, 'referral') ?? data_get($payload, 'postback.referral');
        $candidate = data_get($referral, 'ads_context_data.campaign_id');

        return filled($candidate) ? (string) $candidate : null;
    }

    protected function extractNestedValue(array $payload, string $needle): mixed
    {
        foreach ($payload as $key => $value) {
            if ($key === $needle) {
                return $value;
            }

            if (is_array($value)) {
                $nested = $this->extractNestedValue($value, $needle);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }
}
