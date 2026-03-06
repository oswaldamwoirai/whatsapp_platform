<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Message;
use App\Models\ChatbotFlow;
use App\Models\ChatbotNode;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Process incoming message and trigger chatbot if needed
     */
    public function processMessage(Message $message): void
    {
        $contact = $message->contact;
        
        // Find active chatbot flows that match the message
        $flows = ChatbotFlow::active()->get();
        
        foreach ($flows as $flow) {
            if ($flow->matchesTrigger($message->content)) {
                $this->executeFlow($flow, $contact, $message);
                break; // Only execute the first matching flow
            }
        }
    }

    /**
     * Execute a chatbot flow
     */
    public function executeFlow(ChatbotFlow $flow, Contact $contact, ?Message $triggerMessage = null): void
    {
        $conversation = $contact->activeConversation();
        
        if (!$conversation) {
            $conversation = Conversation::create([
                'contact_id' => $contact->id,
                'status' => 'open',
            ]);
        }

        $context = [
            'contact' => $contact,
            'conversation' => $conversation,
            'trigger_message' => $triggerMessage,
            'user_input' => $triggerMessage?->content ?? '',
        ];

        // Start from the root node
        $startNode = $flow->startNode();
        
        if ($startNode) {
            $this->executeNode($startNode, $context);
        }
    }

    /**
     * Execute a single chatbot node
     */
    public function executeNode(ChatbotNode $node, array $context): void
    {
        try {
            $result = $node->execute($context);
            
            Log::info('Chatbot node executed', [
                'node_id' => $node->id,
                'node_type' => $node->type,
                'result' => $result,
            ]);

            switch ($result['type']) {
                case 'message':
                    $this->sendChatbotMessage($result, $context);
                    break;
                    
                case 'condition':
                    $this->handleCondition($result, $context);
                    break;
                    
                case 'delay':
                    $this->handleDelay($result, $context);
                    break;
                    
                case 'action':
                    $this->handleAction($result, $context);
                    break;
                    
                case 'input':
                    $this->handleInput($result, $context);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Failed to execute chatbot node', [
                'node_id' => $node->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send chatbot message
     */
    protected function sendChatbotMessage(array $result, array $context): void
    {
        $contact = $context['contact'];
        $conversation = $context['conversation'];
        
        // Create message record
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'direction' => 'outbound',
            'type' => 'text',
            'content' => $result['content'],
            'status' => 'pending',
            'metadata' => [
                'chatbot' => true,
                'node_id' => $result['node_id'] ?? null,
            ],
        ]);

        // Send via WhatsApp
        try {
            $response = $this->whatsappService->sendTextMessage(
                $contact->phone,
                $result['content']
            );

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
        } catch (\Exception $e) {
            Log::error('Failed to send chatbot message', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
            
            $message->markAsFailed();
        }
    }

    /**
     * Handle condition node
     */
    protected function handleCondition(array $result, array $context): void
    {
        $nextNode = $result['next_node'] ?? null;
        
        if ($nextNode) {
            $this->executeNode($nextNode, $context);
        }
    }

    /**
     * Handle delay node
     */
    protected function handleDelay(array $result, array $context): void
    {
        $delay = $result['delay'] ?? 1;
        
        // Queue the next node execution with delay
        // This would typically be handled by a job
        Log::info('Chatbot delay scheduled', [
            'delay' => $delay,
            'context' => $context,
        ]);
        
        // For now, we'll just continue immediately
        // In production, you would dispatch a job with delay
        $this->continueToNextNode($context);
    }

    /**
     * Handle action node
     */
    protected function handleAction(array $result, array $context): void
    {
        $action = $result['action'] ?? '';
        
        switch ($action) {
            case 'assign_to_agent':
                $this->assignToAgent($context);
                break;
                
            case 'add_tag':
                $this->addTagToContact($context);
                break;
                
            case 'send_notification':
                $this->sendNotification($context);
                break;
                
            default:
                Log::warning('Unknown chatbot action', ['action' => $action]);
        }
        
        $this->continueToNextNode($context);
    }

    /**
     * Handle input node
     */
    protected function handleInput(array $result, array $context): void
    {
        $prompt = $result['prompt'] ?? '';
        $variable = $result['variable'] ?? 'input';
        
        // Send the prompt message
        $this->sendChatbotMessage([
            'type' => 'message',
            'content' => $prompt,
        ], $context);
        
        // Wait for user input
        // This would typically involve storing the context and waiting for the next message
        Log::info('Chatbot waiting for input', [
            'variable' => $variable,
            'context' => $context,
        ]);
    }

    /**
     * Continue to the next node in the flow
     */
    protected function continueToNextNode(array $context): void
    {
        // This would typically get the next node from the current context
        // For now, we'll just log it
        Log::info('Chatbot continuing to next node', ['context' => $context]);
    }

    /**
     * Assign conversation to agent
     */
    protected function assignToAgent(array $context): void
    {
        $conversation = $context['conversation'];
        
        // Find available agent
        $agent = $this->findAvailableAgent();
        
        if ($agent) {
            $conversation->assignTo($agent);
            
            Log::info('Conversation assigned to agent', [
                'conversation_id' => $conversation->id,
                'agent_id' => $agent->id,
            ]);
        }
    }

    /**
     * Add tag to contact
     */
    protected function addTagToContact(array $context): void
    {
        $contact = $context['contact'];
        
        // This would typically get the tag from the node config
        $tagName = 'chatbot_interaction';
        
        $tag = \App\Models\Tag::firstOrCreate(['name' => $tagName]);
        $contact->addTag($tag);
        
        Log::info('Tag added to contact', [
            'contact_id' => $contact->id,
            'tag' => $tagName,
        ]);
    }

    /**
     * Send notification
     */
    protected function sendNotification(array $context): void
    {
        // This would send a notification to administrators
        Log::info('Chatbot notification sent', ['context' => $context]);
    }

    /**
     * Find available agent
     */
    protected function findAvailableAgent(): ?\App\Models\User
    {
        // Find an active user with operator role
        return \App\Models\User::role('operator')
            ->active()
            ->orderBy('last_login_at', 'desc')
            ->first();
    }

    /**
     * Test a chatbot flow
     */
    public function testFlow(ChatbotFlow $flow, string $testMessage): array
    {
        $testContact = Contact::create([
            'name' => 'Test Contact',
            'phone' => '1234567890',
            'status' => 'active',
        ]);

        $testMessage = Message::create([
            'conversation_id' => 1,
            'contact_id' => $testContact->id,
            'direction' => 'inbound',
            'type' => 'text',
            'content' => $testMessage,
            'status' => 'delivered',
        ]);

        try {
            $this->executeFlow($flow, $testContact, $testMessage);
            
            return [
                'success' => true,
                'message' => 'Flow executed successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } finally {
            // Clean up test data
            $testContact->delete();
            $testMessage->delete();
        }
    }

    /**
     * Get active chatbot flows
     */
    public function getActiveFlows(): \Illuminate\Database\Eloquent\Collection
    {
        return ChatbotFlow::active()->with('nodes')->get();
    }

    /**
     * Get flow statistics
     */
    public function getFlowStatistics(ChatbotFlow $flow): array
    {
        $totalMessages = Message::where('metadata->chatbot', true)
            ->whereHas('conversation.contact', function ($query) use ($flow) {
                // This would need to be implemented based on how you track flow usage
            })
            ->count();

        return [
            'total_messages' => $totalMessages,
            'nodes_count' => $flow->nodes()->count(),
            'trigger_keywords' => $flow->trigger_keywords,
            'status' => $flow->status,
        ];
    }
}
