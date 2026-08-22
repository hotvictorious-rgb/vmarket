<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppJob;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\CustomerAiRelationshipEngine;
use App\Services\WhatsAppCustomerTransformer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WhatsAppCrmController extends Controller
{
    /**
     * [AI] Main Multi-Agent Team Inbox View
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $department = $request->query('department', 'all');
        $search = $request->query('search', '');

        $query = WhatsAppConversation::with(['latestMessage', 'assignedAgent', 'customer'])
            ->orderBy('last_message_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($department !== 'all') {
            $query->where('assigned_department', $department);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $conversations = $query->paginate(25);

        // Calculate counts
        $openCount = WhatsAppConversation::where('status', 'open')->count();
        $botCount = WhatsAppConversation::where('status', 'bot_handling')->count();
        $disputeCount = WhatsAppConversation::where('priority', 'urgent_dispute')->count();

        return view('admin-views.whatsapp-crm.index', compact('conversations', 'status', 'openCount', 'botCount', 'disputeCount', 'search'));
    }

    /**
     * [AI] Fetch Conversation Thread Messages (AJAX)
     */
    public function getMessages(int $id): JsonResponse
    {
        $conversation = WhatsAppConversation::with(['assignedAgent', 'customer'])->findOrFail($id);
        
        // Acquire / extend agent lock (300 seconds)
        $adminId = auth('admin')->id();
        $conversation->update([
            'locked_until' => now()->addMinutes(5),
            'locked_by_agent_id' => $adminId,
            'unread_agent_count' => 0,
        ]);

        $messages = WhatsAppMessage::where('conversation_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        $dossier = CustomerAiRelationshipEngine::buildCustomerDossier($conversation->phone);

        return response()->json([
            'status' => true,
            'conversation' => $conversation,
            'messages' => $messages,
            'dossier' => $dossier,
        ]);
    }

    /**
     * [AI] Send Agent Reply Message (AJAX)
     */
    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'message' => 'required_without:media_url|string|nullable',
            'media_url' => 'nullable|string',
            'media_type' => 'nullable|string|in:image,document,audio,video',
        ]);

        $conversation = WhatsAppConversation::findOrFail($id);
        $adminId = auth('admin')->id();

        // Switch status to open and assign to current agent
        $conversation->update([
            'assigned_agent_id' => $adminId,
            'status' => 'open',
        ]);

        $payload = [];
        $type = 'text';

        if (!empty($request->media_url)) {
            $type = $request->media_type ?? 'image';
            $payload = [
                'media_url' => $request->media_url,
                'caption' => $request->message,
            ];
        } else {
            $payload = ['text' => $request->message];
        }

        // Dispatch outbound background job
        dispatch(new SendWhatsAppJob(
            $conversation->phone,
            $type,
            $payload,
            $conversation->id,
            $adminId
        ));

        return response()->json([
            'status' => true,
            'message' => 'Message queued for dispatch.',
        ]);
    }

    /**
     * [AI] Update Conversation Status / Transfer (AJAX)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $conversation = WhatsAppConversation::findOrFail($id);

        $updateData = [];
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }
        if ($request->has('priority')) {
            $updateData['priority'] = $request->priority;
        }
        if ($request->has('assigned_agent_id')) {
            $updateData['assigned_agent_id'] = $request->assigned_agent_id;
        }
        if ($request->has('internal_notes')) {
            $updateData['internal_notes'] = $request->internal_notes;
        }

        $conversation->update($updateData);

        return response()->json([
            'status' => true,
            'message' => 'Conversation updated successfully.',
        ]);
    }
}
