<?php

namespace Modules\CRM\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Modules\CRM\Models\Conversation;
use Modules\CRM\Models\Message;
use Modules\Lead\Models\Lead;
use RuntimeException;

class MetaFacebookService
{
    public function sendText(
        string $recipientId,
        string $body,
        string $platform = 'facebook',
        ?int $conversationId = null,
        ?int $leadId = null,
        ?int $userId = null,
    ): array {
        try {
            $response = Http::withToken($this->token($platform))
                ->acceptJson()
                ->post("https://graph.facebook.com/{$this->apiVersion()}/me/messages", [
                    'recipient'      => ['id' => $recipientId],
                    'message'        => ['text' => $body],
                    'messaging_type' => 'RESPONSE',
                ]);

            if ($response->failed()) {
                Log::error('Facebook/Instagram send text failed.', ['platform' => $platform, 'status' => $response->status(), 'body' => $response->json()]);
                throw new RuntimeException($response->json('error.message') ?: 'Facebook API request failed.');
            }

            $this->persistOutbound($response->json(), $body, 'text', $conversationId, $leadId, $userId, $platform);

            return $response->json();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['recipient_id' => $recipientId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function sendAttachment(
        string $recipientId,
        string $type,
        string $publicUrl,
        string $platform = 'facebook',
        ?int $conversationId = null,
        ?int $leadId = null,
        ?int $userId = null,
    ): array {
        try {
            $fbType = match ($type) {
                'image' => 'image',
                'audio' => 'audio',
                'video' => 'video',
                default => 'file',
            };

            $response = Http::withToken($this->token($platform))
                ->acceptJson()
                ->post("https://graph.facebook.com/{$this->apiVersion()}/me/messages", [
                    'recipient'      => ['id' => $recipientId],
                    'message'        => [
                        'attachment' => [
                            'type'    => $fbType,
                            'payload' => ['url' => $publicUrl, 'is_reusable' => true],
                        ],
                    ],
                    'messaging_type' => 'RESPONSE',
                ]);

            if ($response->failed()) {
                Log::error('Facebook/Instagram send attachment failed.', ['platform' => $platform, 'status' => $response->status(), 'body' => $response->json()]);
                throw new RuntimeException($response->json('error.message') ?: 'Facebook API attachment failed.');
            }

            $this->persistOutbound($response->json(), "[$type]", $type, $conversationId, $leadId, $userId, $platform);

            return $response->json();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['recipient_id' => $recipientId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getProfile(string $userId, string $platform = 'facebook'): array
    {
        try {
            $token = (string) config('services.meta_facebook.page_access_token');

            if (blank($token)) return [];

            $fields = $platform === 'instagram' ? 'name,username,profile_pic' : 'name,profile_pic';

            $response = Http::withToken($token)
                ->acceptJson()
                ->get("https://graph.facebook.com/{$this->apiVersion()}/{$userId}", [
                    'fields' => $fields,
                ]);

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
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

    protected function persistOutbound(array $apiResponse, ?string $body, string $type, ?int $conversationId, ?int $leadId, ?int $userId, string $platform = 'facebook'): void
    {
        if ($leadId && !$conversationId) {
            $lead = Lead::find($leadId);
            if ($lead) {
                $conversation = Conversation::firstOrCreate(
                    ['lead_id' => $lead->id, 'platform' => $platform],
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
            'wa_message_id'   => $apiResponse['message_id'] ?? null,
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

    private function token(string $platform): string
    {
        $token = $platform === 'instagram'
            ? (string) (config('services.meta_facebook.instagram_access_token') ?: config('services.meta_facebook.page_access_token'))
            : (string) config('services.meta_facebook.page_access_token');

        if (blank($token)) {
            throw new RuntimeException($platform === 'instagram'
                ? 'Instagram access token is not configured.'
                : 'Facebook Page Access Token is not configured.');
        }

        return $token;
    }

    private function apiVersion(): string
    {
        return (string) config('services.meta_facebook.api_version', 'v20.0');
    }
}
