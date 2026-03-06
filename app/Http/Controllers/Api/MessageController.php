<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\MediaFile;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Display a listing of the messages.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Message::with(['contact', 'conversation', 'user']);

        // Filter by conversation
        if ($request->has('conversation_id')) {
            $query->where('conversation_id', $request->conversation_id);
        }

        // Filter by contact
        if ($request->has('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        // Filter by direction
        if ($request->has('direction')) {
            $query->where('direction', $request->direction);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('content', 'like', "%{$search}%");
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $messages = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Display the specified message.
     */
    public function show(Message $message): JsonResponse
    {
        $message->load(['contact', 'conversation', 'user']);

        return response()->json([
            'success' => true,
            'data' => $message,
        ]);
    }

    /**
     * Send a message
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'type' => ['required', Rule::in(['text', 'image', 'video', 'document', 'audio', 'interactive', 'template', 'location'])],
            'content' => 'required|string',
            'media_url' => 'nullable|array',
            'media_url.*' => 'string',
            'interactive_type' => 'nullable|string',
            'interactive_buttons' => 'nullable|array',
            'interactive_list' => 'nullable|array',
            'template_name' => 'nullable|string',
            'template_components' => 'nullable|array',
            'location' => 'nullable|array',
            'schedule_at' => 'nullable|date|after:now',
        ]);

        $contact = Contact::findOrFail($validated['contact_id']);
        $conversation = $contact->activeConversation() ?? Conversation::create([
            'contact_id' => $contact->id,
            'status' => 'open',
        ]);

        // Create message record
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'user_id' => auth()->id(),
            'direction' => 'outbound',
            'type' => $validated['type'],
            'content' => $validated['content'],
            'media_url' => $validated['media_url'] ?? null,
            'status' => 'pending',
            'metadata' => [
                'interactive_type' => $validated['interactive_type'] ?? null,
                'interactive_buttons' => $validated['interactive_buttons'] ?? null,
                'interactive_list' => $validated['interactive_list'] ?? null,
                'template_name' => $validated['template_name'] ?? null,
                'template_components' => $validated['template_components'] ?? null,
                'location' => $validated['location'] ?? null,
            ],
        ]);

        try {
            $response = $this->sendMessageViaWhatsApp($contact, $validated);

            // Update message with WhatsApp ID
            if (isset($response['messages'][0]['id'])) {
                $message->update([
                    'whatsapp_message_id' => $response['messages'][0]['id'],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            $conversation->updateLastMessage();
            $contact->updateLastMessage();

            return response()->json([
                'success' => true,
                'data' => $message->load(['contact', 'conversation']),
                'message' => 'Message sent successfully',
            ]);
        } catch (\Exception $e) {
            $message->markAsFailed();

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send bulk messages
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
            'type' => ['required', Rule::in(['text', 'image', 'video', 'document', 'template'])],
            'content' => 'required|string',
            'media_url' => 'nullable|array',
            'template_name' => 'nullable|string',
            'template_components' => 'nullable|array',
            'delay_between_messages' => 'nullable|integer|min:1|max:60',
        ]);

        $contacts = Contact::whereIn('id', $validated['contact_ids'])->get();
        $results = [];
        $delay = $validated['delay_between_messages'] ?? 1;

        foreach ($contacts as $index => $contact) {
            try {
                $conversation = $contact->activeConversation() ?? Conversation::create([
                    'contact_id' => $contact->id,
                    'status' => 'open',
                ]);

                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'user_id' => auth()->id(),
                    'direction' => 'outbound',
                    'type' => $validated['type'],
                    'content' => $validated['content'],
                    'media_url' => $validated['media_url'] ?? null,
                    'status' => 'pending',
                    'metadata' => [
                        'template_name' => $validated['template_name'] ?? null,
                        'template_components' => $validated['template_components'] ?? null,
                    ],
                ]);

                $response = $this->sendMessageViaWhatsApp($contact, $validated);

                if (isset($response['messages'][0]['id'])) {
                    $message->update([
                        'whatsapp_message_id' => $response['messages'][0]['id'],
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }

                $conversation->updateLastMessage();
                $contact->updateLastMessage();

                $results[] = [
                    'contact_id' => $contact->id,
                    'message_id' => $message->id,
                    'status' => 'sent',
                ];

                // Add delay between messages to respect rate limits
                if ($delay > 0 && $index < $contacts->count() - 1) {
                    sleep($delay);
                }
            } catch (\Exception $e) {
                $results[] = [
                    'contact_id' => $contact->id,
                    'message_id' => $message->id ?? null,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'total_contacts' => $contacts->count(),
                'sent_count' => collect($results)->where('status', 'sent')->count(),
                'failed_count' => collect($results)->where('status', 'failed')->count(),
            ],
            'message' => 'Bulk messages processed',
        ]);
    }

    /**
     * Resend a failed message
     */
    public function resend(Message $message): JsonResponse
    {
        if (!$message->isFailed()) {
            return response()->json([
                'success' => false,
                'message' => 'Only failed messages can be resent',
            ], 400);
        }

        try {
            $messageData = [
                'type' => $message->type,
                'content' => $message->content,
                'media_url' => $message->media_url,
            ];

            // Add metadata for special message types
            if ($message->metadata) {
                $messageData = array_merge($messageData, $message->metadata);
            }

            $response = $this->sendMessageViaWhatsApp($message->contact, $messageData);

            if (isset($response['messages'][0]['id'])) {
                $message->update([
                    'whatsapp_message_id' => $response['messages'][0]['id'],
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $message->load(['contact', 'conversation']),
                'message' => 'Message resent successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Message $message): JsonResponse
    {
        if ($message->isInbound() && $message->whatsapp_message_id) {
            try {
                $this->whatsappService->markMessageAsRead($message->whatsapp_message_id);
                $message->markAsRead();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark message as read: ' . $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
        ]);
    }

    /**
     * Get message statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_messages' => Message::count(),
            'sent_messages' => Message::outbound()->count(),
            'received_messages' => Message::inbound()->count(),
            'pending_messages' => Message::pending()->count(),
            'sent_count' => Message::sent()->count(),
            'delivered_count' => Message::delivered()->count(),
            'read_count' => Message::read()->count(),
            'failed_count' => Message::failed()->count(),
            'today_messages' => Message::whereDate('created_at', today())->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Send message via WhatsApp service
     */
    protected function sendMessageViaWhatsApp(Contact $contact, array $data): array
    {
        switch ($data['type']) {
            case 'text':
                return $this->whatsappService->sendTextMessage($contact->phone, $data['content']);
                
            case 'image':
                $imageUrl = $data['media_url'][0] ?? '';
                return $this->whatsappService->sendImageMessage($contact->phone, $imageUrl, $data['content']);
                
            case 'video':
                $videoUrl = $data['media_url'][0] ?? '';
                return $this->whatsappService->sendVideoMessage($contact->phone, $videoUrl, $data['content']);
                
            case 'document':
                $documentUrl = $data['media_url'][0] ?? '';
                return $this->whatsappService->sendDocumentMessage($contact->phone, $documentUrl, 'document.pdf', $data['content']);
                
            case 'interactive':
                if ($data['interactive_type'] === 'button') {
                    return $this->whatsappService->sendButtonMessage($contact->phone, $data['content'], $data['interactive_buttons']);
                } elseif ($data['interactive_type'] === 'list') {
                    return $this->whatsappService->sendListMessage($contact->phone, $data['content'], 'Select Option', $data['interactive_list']);
                }
                break;
                
            case 'template':
                return $this->whatsappService->sendTemplateMessage($contact->phone, $data['template_name'], $data['template_components'] ?? []);
                
            case 'location':
                $location = $data['location'];
                return $this->whatsappService->sendLocationMessage(
                    $contact->phone,
                    $location['latitude'],
                    $location['longitude'],
                    $location['name'],
                    $location['address'] ?? null
                );
        }

        throw new \InvalidArgumentException("Unsupported message type: {$data['type']}");
    }
}
