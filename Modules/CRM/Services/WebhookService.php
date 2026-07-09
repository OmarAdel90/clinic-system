<?php

namespace Modules\CRM\Services;

use Modules\CRM\Models\Conversation;
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

        if (!$waId) {
            Log::warning('WhatsApp webhook missing wa_id', ['msg' => $msgData]);
            return;
        }

        if ($messageId && Message::where('wa_message_id', $messageId)->exists()) {
            return;
        }

        $lead = Lead::firstOrCreate(
            ['whatsapp_id' => $waId],
            [
                'platform'    => 'whatsapp',
                'phone'       => $phone,
                'name'        => $profileName,
                'profile_name' => $profileName,
                'metadata'    => ['source' => 'whatsapp_webhook'],
            ]
        );

        if (!$lead->wasRecentlyCreated && $profileName && $lead->name !== $profileName) {
            $lead->update(['profile_name' => $profileName, 'name' => $profileName]);
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

        if (!$psid || (!$text && empty($attachments))) return;

        $lead = Lead::firstOrCreate(
            ['whatsapp_id' => $psid, 'platform' => 'facebook'],
            [
                'phone'    => null,
                'name'     => null,
                'platform' => 'facebook',
                'metadata' => ['source' => 'facebook_webhook'],
            ]
        );

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

        if (!$igid || (!$text && empty($attachments))) return;

        $lead = Lead::firstOrCreate(
            ['whatsapp_id' => $igid, 'platform' => 'instagram'],
            [
                'phone'    => null,
                'name'     => null,
                'platform' => 'instagram',
                'metadata' => ['source' => 'instagram_webhook'],
            ]
        );

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
                'url'     => null,
                'caption' => $media['caption'] ?? null,
                'mime'    => $media['mime_type'] ?? null,
                'size'    => $media['sha256'] ?? null,
            ];
        }

        return null;
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
}
