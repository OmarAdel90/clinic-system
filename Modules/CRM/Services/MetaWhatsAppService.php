<?php

namespace Modules\CRM\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Database\QueryException;
use Modules\CRM\Models\Conversation;
use Modules\CRM\Models\Message;
use Modules\Lead\Models\Lead;
use RuntimeException;

class MetaWhatsAppService
{
    public function sendText(
        string $to,
        string $body,
        ?string $replyToMessageId = null,
        ?int $conversationId = null,
        ?int $leadId = null,
        ?int $userId = null,
    ): array {
        try {
            $config = $this->configuredMeta();
            $to = $this->normalizePhoneNumber($to);

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $to,
                'type'              => 'text',
                'text'              => ['preview_url' => false, 'body' => $body],
            ];

            if (filled($replyToMessageId)) {
                $payload['context'] = ['message_id' => $replyToMessageId];
            }

            $response = Http::withToken($config['access_token'])
                ->acceptJson()
                ->post($this->messagesUrl($config), $payload);

            if ($response->failed()) {
                Log::error('Meta WhatsApp send failed.', ['status' => $response->status(), 'body' => $response->json()]);
                throw new RuntimeException($response->json('error.message') ?: 'Meta WhatsApp API request failed.');
            }

            $this->persistOutbound($response->json(), $body, 'text', $conversationId, $leadId, $userId);

            return $response->json();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['to' => $to, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function sendInteractiveChoices(
        string $to,
        string $body,
        array $choices,
        ?int $conversationId = null,
        ?int $leadId = null,
        ?int $userId = null,
    ): array {
        try {
            $config = $this->configuredMeta();
            $to = $this->normalizePhoneNumber($to);
            $choices = collect($choices)
                ->map(fn ($choice, $index) => [
                    'id'          => $this->choiceId((string) ($choice['id'] ?? ($index + 1))),
                    'title'       => trim((string) ($choice['title'] ?? 'Option ' . ($index + 1))),
                    'description' => trim((string) ($choice['description'] ?? '')),
                ])
                ->filter(fn ($choice) => $choice['title'] !== '')
                ->values();

            if ($choices->isEmpty()) {
                return $this->sendText($to, $body, null, $conversationId, $leadId, $userId);
            }

            $useButtons = $choices->count() <= 3 && $choices->every(fn ($choice) => mb_strlen($choice['title']) <= 20);

            $interactive = $useButtons
                ? [
                    'type'   => 'button',
                    'body'   => ['text' => $body],
                    'action' => [
                        'buttons' => $choices->map(fn ($choice) => [
                            'type'  => 'reply',
                            'reply' => [
                                'id'    => $choice['id'],
                                'title' => $this->limitText($choice['title'], 20),
                            ],
                        ])->all(),
                    ],
                ]
                : [
                    'type'   => 'list',
                    'body'   => ['text' => $body],
                    'action' => [
                        'button'   => 'Choose',
                        'sections' => [[
                            'title' => 'Options',
                            'rows'  => $choices->take(10)->map(fn ($choice) => array_filter([
                                'id'          => $choice['id'],
                                'title'       => $this->limitText($choice['title'], 24),
                                'description' => $this->limitText($choice['description'], 72),
                            ]))->all(),
                        ]],
                    ],
                ];

            $response = Http::withToken($config['access_token'])
                ->acceptJson()
                ->post($this->messagesUrl($config), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $to,
                    'type'              => 'interactive',
                    'interactive'       => $interactive,
                ]);

            if ($response->failed()) {
                Log::error('Meta WhatsApp interactive send failed.', ['status' => $response->status(), 'body' => $response->json()]);
                throw new RuntimeException($response->json('error.message') ?: 'Meta WhatsApp interactive request failed.');
            }

            $this->persistOutbound($response->json(), $body, 'interactive', $conversationId, $leadId, $userId);

            return $response->json();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['to' => $to, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function sendMedia(
        string $to,
        string $type,
        string $mediaId,
        ?string $caption = null,
        ?string $filename = null,
        ?string $replyToMessageId = null,
        ?int $conversationId = null,
        ?int $leadId = null,
        ?int $userId = null,
    ): array {
        try {
            $config = $this->configuredMeta();
            $to = $this->normalizePhoneNumber($to);
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $to,
                'type'              => $type,
                $type               => ['id' => $mediaId],
            ];

            if ($caption && in_array($type, ['image', 'document'], true)) {
                $payload[$type]['caption'] = $caption;
            }
            if ($filename && $type === 'document') {
                $payload[$type]['filename'] = $filename;
            }
            if (filled($replyToMessageId)) {
                $payload['context'] = ['message_id' => $replyToMessageId];
            }

            $response = Http::withToken($config['access_token'])
                ->acceptJson()
                ->post($this->messagesUrl($config), $payload);

            if ($response->failed()) {
                Log::error('Meta WhatsApp media send failed.', ['status' => $response->status(), 'body' => $response->json()]);
                throw new RuntimeException($response->json('error.message') ?: 'Meta WhatsApp API request failed.');
            }

            $this->persistOutbound($response->json(), $caption ?? "[$type]", $type, $conversationId, $leadId, $userId);

            return $response->json();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['to' => $to, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function uploadMedia(string $path, string $mimeType): string
    {
        try {
            $config = $this->configuredMeta();
            $url = sprintf('https://graph.facebook.com/%s/%s/media', $config['api_version'], $config['phone_number_id']);
            $response = Http::withToken($config['access_token'])
                ->attach('file', fopen($path, 'r'), basename($path), ['Content-Type' => $mimeType])
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'type'              => $mimeType,
                ]);

            if ($response->failed()) {
                Log::error('Meta WhatsApp media upload failed.', ['status' => $response->status(), 'body' => $response->json()]);
                throw new RuntimeException($response->json('error.message') ?: 'Meta WhatsApp media upload failed.');
            }

            return (string) $response->json('id');
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function downloadMedia(string $mediaId, ?string $filename = null): ?array
    {
        try {
            $config = $this->configuredMeta();
            $meta = Http::withToken($config['access_token'])
                ->acceptJson()
                ->get(sprintf('https://graph.facebook.com/%s/%s', $config['api_version'], $mediaId));

            if ($meta->failed() || blank($meta->json('url'))) {
                Log::warning('Meta WhatsApp media metadata fetch failed.', ['media_id' => $mediaId, 'status' => $meta->status()]);
                return null;
            }

            $file = Http::withToken($config['access_token'])->get($meta->json('url'));
            if ($file->failed()) {
                Log::warning('Meta WhatsApp media download failed.', ['media_id' => $mediaId, 'status' => $file->status()]);
                return null;
            }

            $mime = $this->normalizeMimeType($meta->json('mime_type') ?: $file->header('Content-Type') ?: 'application/octet-stream');
            $extension = $this->extensionFromMime($mime);
            $safeName = $filename ? pathinfo($filename, PATHINFO_FILENAME) : $mediaId;
            $safeName = str($safeName)->slug('-')->limit(80, '')->toString() ?: $mediaId;
            $relativePath = 'uploads/whatsapp-media/' . date('Y/m') . '/' . $safeName . '-' . uniqid() . '.' . $extension;
            $absolutePath = public_path($relativePath);

            File::ensureDirectoryExists(dirname($absolutePath));
            $contents = $file->body();
            File::put($absolutePath, $contents);

            return [
                'path'     => $relativePath,
                'mime_type' => $mime,
                'filename' => $this->downloadFilename($filename, $safeName, $extension),
                'size' => strlen($contents),
            ];
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['media_id' => $mediaId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function mediaTypeFromMime(string $mimeType): string
    {
        try {
            if (str_starts_with($mimeType, 'image/')) return 'image';
            if (str_starts_with($mimeType, 'audio/')) return 'audio';
            if (str_starts_with($mimeType, 'video/')) return 'video';
            return 'document';
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function supportedMimeType(?string $extension, ?string $detectedMime): string
    {
        try {
            $extension = strtolower((string) $extension);
            $supported = [
                'aac'  => 'audio/aac',
                'm4a'  => 'audio/mp4',
                'mp3'  => 'audio/mpeg',
                'amr'  => 'audio/amr',
                'ogg'  => 'audio/ogg',
                'opus' => 'audio/opus',
                'webm' => 'audio/ogg',
                'ppt'  => 'application/vnd.ms-powerpoint',
                'doc'  => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pdf'  => 'application/pdf',
                'txt'  => 'text/plain',
                'xls'  => 'application/vnd.ms-excel',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'webp' => 'image/webp',
                'mp4'  => 'video/mp4',
                '3gp'  => 'video/3gpp',
            ];

            if (isset($supported[$extension])) return $supported[$extension];
            if ($detectedMime && in_array($detectedMime, $supported, true)) return $detectedMime;

            throw new RuntimeException('This file type is not supported by WhatsApp.');
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function persistOutbound(array $apiResponse, ?string $body, string $type, ?int $conversationId, ?int $leadId, ?int $userId): void
    {
        if ($leadId && !$conversationId) {
            $lead = Lead::find($leadId);
            if ($lead) {
                $conversation = Conversation::firstOrCreate(
                    ['lead_id' => $lead->id, 'platform' => 'whatsapp'],
                    ['status' => 'active', 'first_message_time' => now()]
                );
                $conversationId = $conversation->id;
            }
        }

        if (!$conversationId && !$leadId) return;

        $message = Message::create(array_filter([
            'conversation_id' => $conversationId,
            'lead_id'         => $leadId,
            'user_id'         => $userId ?? auth()->id(),
            'wa_message_id'   => $apiResponse['messages'][0]['id'] ?? null,
            'direction'       => 'outbound',
            'type'            => $type,
            'body'            => $body,
            'payload'         => $apiResponse,
            'status'          => 'sent',
            'sent_at'         => now(),
        ]));

        if ($conversationId) {
            Conversation::where('id', $conversationId)->update([
                'last_message_time' => now(),
            ]);
        }
    }

    private function configuredMeta(): array
    {
        $config = config('services.meta_whatsapp');

        if (blank($config['access_token']) || blank($config['phone_number_id'])) {
            throw new RuntimeException('Meta WhatsApp credentials are not configured.');
        }

        return $config;
    }

    private function messagesUrl(array $config): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $config['api_version'],
            $config['phone_number_id']
        );
    }

    private function choiceId(string $value): string
    {
        $id = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $value) ?: uniqid('choice_', false);
        return $this->limitText($id, 200);
    }

    private function limitText(string $value, int $limit): string
    {
        $value = trim($value);
        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $number = preg_replace('/\D+/', '', $phone) ?: $phone;

        if (str_starts_with($number, '01') && strlen($number) === 11) return '2' . $number;
        if (str_starts_with($number, '2') && in_array(substr($number, 1, 2), ['10', '11', '12', '15'], true) && strlen($number) === 11) return '20' . substr($number, 1);
        if (in_array(substr($number, 0, 2), ['10', '11', '12', '15'], true) && strlen($number) === 10) return '20' . $number;

        return $number;
    }

    private function extensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'audio/aac' => 'aac',
            'audio/amr' => 'amr',
            'audio/mp4', 'audio/m4a' => 'm4a',
            'audio/mpeg' => 'mp3',
            'audio/ogg', 'audio/opus' => 'ogg',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/3gpp' => '3gp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'text/plain' => 'txt',
            default => 'bin',
        };
    }

    private function normalizeMimeType(string $mimeType): string
    {
        return strtolower(trim(str($mimeType)->before(';')->toString()));
    }

    private function downloadFilename(?string $filename, string $safeName, string $extension): string
    {
        if ($filename) {
            $originalExtension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
            if ($originalExtension === $extension) return $filename;
            return pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
        }
        return $safeName . '.' . $extension;
    }
}
