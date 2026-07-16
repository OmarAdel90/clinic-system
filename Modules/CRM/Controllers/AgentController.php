<?php

namespace Modules\CRM\Controllers;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Conversation;
use Modules\CRM\Models\FollowUp;
use Modules\CRM\Models\Message;
use Modules\CRM\Services\PerformanceMetricsService;
use Modules\CRM\Services\MetaFacebookService;
use Modules\CRM\Services\MetaWhatsAppService;
use Modules\Auth\Models\User;
use Modules\Lead\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(
        protected PerformanceMetricsService $performanceMetricsService,
    ) {}

    public function conversations(): JsonResponse
    {
        $user = auth()->user();

        $conversations = Conversation::with('lead.leadStatus')
            ->where('assigned_user_id', $user->id)
            ->orderBy('last_message_time', 'desc')
            ->get();

        return response()->json($conversations);
    }

    public function leads(): JsonResponse
    {
        $user = auth()->user();

        $leadIds = Conversation::where('assigned_user_id', $user->id)
            ->pluck('lead_id');

        $leads = Lead::with('leadStatus', 'conversations')
            ->whereIn('id', $leadIds)
            ->get();

        return response()->json($leads);
    }

    public function followups(): JsonResponse
    {
        $user = auth()->user();

        $followups = FollowUp::with('conversation.lead')
            ->where('user_id', $user->id)
            ->pending()
            ->orderBy('due_at', 'asc')
            ->get();

        return response()->json($followups);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $actor = $request->user();

        abort_unless(
            $conversation->assigned_user_id === $actor->id || $actor->can('view_any_user'),
            403,
            'You are not allowed to view this conversation.',
        );

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('user')
            ->latest('sent_at')
            ->latest('created_at')
            ->take(100)
            ->get()
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function completeFollowup(Request $request, FollowUp $followup): JsonResponse
    {
        $actor = $request->user();

        abort_unless(
            $followup->user_id === $actor->id || $actor->can('view_any_user'),
            403,
            'You are not allowed to update this follow-up.',
        );

        if (! $followup->completed_at) {
            $followup->forceFill([
                'completed_at' => now(),
            ])->save();
        }

        return response()->json(
            $followup->fresh(['conversation.lead'])
        );
    }

    public function sendMessage(Request $request, MetaWhatsAppService $whatsapp, MetaFacebookService $facebook): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'body'            => 'required_without:media_url|string',
            'media_url'       => 'required_without:body|string',
            'media_type'      => 'required_with:media_url|string|in:image,audio,video,file',
        ]);

        $conversation = Conversation::findOrFail($request->conversation_id);
        abort_unless(
            $conversation->assigned_user_id === auth()->id() || $request->user()->can('view_any_user'),
            403,
            'You are not allowed to send messages in this conversation.',
        );

        $lead = $conversation->lead;
        $platform = $conversation->platform;

        if (!$lead) {
            return response()->json(['error' => 'Conversation has no associated lead.'], 422);
        }

        try {
            $result = $this->dispatchOutboundMessage(
                $conversation,
                $request->body,
                $request->media_url,
                $request->media_type,
                $whatsapp,
                $facebook,
            );
        } catch (\Throwable $e) {
            Message::create([
                'conversation_id' => $conversation->id,
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'direction' => 'outbound',
                'type' => $request->media_url ? $request->media_type : 'text',
                'body' => $request->body,
                'media_url' => $request->media_url,
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $messages = $this->conversationMessages($conversation->id);

            return response()->json([
                'message' => $e->getMessage(),
                'messages' => $messages,
            ], 422);
        }

        $messages = $this->conversationMessages($conversation->id);

        return response()->json([
            'api_response' => $result,
            'messages'     => $messages,
        ]);
    }

    public function retryMessage(Request $request, Message $message, MetaWhatsAppService $whatsapp, MetaFacebookService $facebook): JsonResponse
    {
        $conversation = $message->conversation()->with('lead')->firstOrFail();
        $actor = $request->user();

        abort_unless(
            $conversation->assigned_user_id === $actor->id || $actor->can('view_any_user'),
            403,
            'You are not allowed to retry messages in this conversation.',
        );

        if ($message->direction !== 'outbound') {
            return response()->json(['message' => 'Only outbound messages can be retried.'], 422);
        }

        if ($message->status !== 'failed') {
            return response()->json(['message' => 'Only failed messages can be retried.'], 422);
        }

        if ($message->type !== 'text') {
            return response()->json(['message' => 'Only failed text messages can be retried right now.'], 422);
        }

        try {
            $result = $this->dispatchOutboundMessage(
                $conversation,
                $message->body,
                null,
                null,
                $whatsapp,
                $facebook,
            );

            $message->update([
                'status' => 'retried',
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $message->update([
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'messages' => $this->conversationMessages($conversation->id),
            ], 422);
        }

        return response()->json([
            'api_response' => $result,
            'messages' => $this->conversationMessages($conversation->id),
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        $actor = $request->user();
        $targetUser = $actor;

        if ($request->filled('user_id') && $actor->can('view_any_user')) {
            $targetUser = User::findOrFail($request->integer('user_id'));
        }

        return response()->json($this->performanceMetricsService->getForUser($targetUser));
    }

    protected function dispatchOutboundMessage(
        Conversation $conversation,
        ?string $body,
        ?string $mediaUrl,
        ?string $mediaType,
        MetaWhatsAppService $whatsapp,
        MetaFacebookService $facebook,
    ): array {
        $lead = $conversation->lead;
        $platform = $conversation->platform;

        if (! $lead) {
            throw new \RuntimeException('Conversation has no associated lead.');
        }

        if ($platform === 'whatsapp') {
            $recipient = $lead->whatsapp_id ?: $lead->phone;

            if (blank($recipient)) {
                throw new \RuntimeException('This WhatsApp lead has no sendable recipient identifier.');
            }

            if ($mediaUrl) {
                return $whatsapp->sendMedia(
                    $recipient,
                    $mediaType,
                    $mediaUrl,
                    caption: $body,
                    conversationId: $conversation->id,
                    leadId: $lead->id,
                    userId: auth()->id()
                );
            }

            return $whatsapp->sendText(
                $recipient,
                (string) $body,
                conversationId: $conversation->id,
                leadId: $lead->id,
                userId: auth()->id()
            );
        }

        $recipient = $lead->whatsapp_id;

        if (blank($recipient)) {
            throw new \RuntimeException('This conversation has no Meta recipient identifier.');
        }

        if ($mediaUrl) {
            return $facebook->sendAttachment(
                $recipient,
                $mediaType,
                $mediaUrl,
                platform: $platform,
                conversationId: $conversation->id,
                leadId: $lead->id,
                userId: auth()->id()
            );
        }

        return $facebook->sendText(
            $recipient,
            (string) $body,
            platform: $platform,
            conversationId: $conversation->id,
            leadId: $lead->id,
            userId: auth()->id()
        );
    }

    protected function conversationMessages(int $conversationId)
    {
        return Message::where('conversation_id', $conversationId)
            ->with('user')
            ->latest('sent_at')
            ->latest('created_at')
            ->take(100)
            ->get()
            ->reverse()
            ->values();
    }
}
