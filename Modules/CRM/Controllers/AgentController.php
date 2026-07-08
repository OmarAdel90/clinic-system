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

        if ($platform === 'whatsapp') {
            if ($request->media_url) {
                $result = $whatsapp->sendMedia(
                    $lead->phone, $request->media_type, $request->media_url,
                    caption: $request->body,
                    conversationId: $conversation->id,
                    leadId: $lead->id,
                    userId: auth()->id()
                );
            } else {
                $result = $whatsapp->sendText(
                    $lead->phone, $request->body,
                    conversationId: $conversation->id,
                    leadId: $lead->id,
                    userId: auth()->id()
                );
            }
        } else {
            if ($request->media_url) {
                $result = $facebook->sendAttachment(
                    $lead->whatsapp_id, $request->media_type, $request->media_url,
                    platform: $platform,
                    conversationId: $conversation->id,
                    leadId: $lead->id,
                    userId: auth()->id()
                );
            } else {
                $result = $facebook->sendText(
                    $lead->whatsapp_id, $request->body,
                    platform: $platform,
                    conversationId: $conversation->id,
                    leadId: $lead->id,
                    userId: auth()->id()
                );
            }
        }

        $messages = Message::where('conversation_id', $conversation->id)
            ->with('user')
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'api_response' => $result,
            'messages'     => $messages,
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
}
