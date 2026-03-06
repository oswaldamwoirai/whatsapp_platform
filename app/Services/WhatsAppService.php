<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Conversation;

class WhatsAppService
{
    protected Client $client;
    protected string $accessToken;
    protected string $phoneNumberId;
    protected string $apiVersion;

    public function __construct()
    {
        $this->accessToken = Setting::get('whatsapp_access_token');
        $this->phoneNumberId = Setting::get('whatsapp_phone_number_id');
        $this->apiVersion = Setting::get('whatsapp_api_version', 'v18.0');

        $this->client = new Client([
            'base_uri' => "https://graph.facebook.com/{$this->apiVersion}/",
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Send a text message
     */
    public function sendTextMessage(string $to, string $message): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send an image message
     */
    public function sendImageMessage(string $to, string $imageUrl, ?string $caption = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl,
                'caption' => $caption,
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send a video message
     */
    public function sendVideoMessage(string $to, string $videoUrl, ?string $caption = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'video',
            'video' => [
                'link' => $videoUrl,
                'caption' => $caption,
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send a document message
     */
    public function sendDocumentMessage(string $to, string $documentUrl, string $filename, ?string $caption = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => [
                'link' => $documentUrl,
                'filename' => $filename,
                'caption' => $caption,
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send an interactive message with buttons
     */
    public function sendButtonMessage(string $to, string $bodyText, array $buttons): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => [
                    'text' => $bodyText,
                ],
                'action' => [
                    'buttons' => $buttons,
                ],
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send an interactive message with list
     */
    public function sendListMessage(string $to, string $bodyText, string $buttonText, array $sections): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'header' => [
                    'type' => 'text',
                    'text' => $buttonText,
                ],
                'body' => [
                    'text' => $bodyText,
                ],
                'action' => [
                    'button' => $buttonText,
                    'sections' => $sections,
                ],
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send a template message
     */
    public function sendTemplateMessage(string $to, string $templateName, array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => 'en',
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->sendMessage($payload);
    }

    /**
     * Send location message
     */
    public function sendLocationMessage(string $to, float $latitude, float $longitude, string $name, ?string $address = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'location',
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'name' => $name,
                'address' => $address,
            ],
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Send contact message
     */
    public function sendContactMessage(string $to, array $contacts): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'contacts',
            'contacts' => $contacts,
        ];

        return $this->sendMessage($payload);
    }

    /**
     * Mark message as read
     */
    public function markMessageAsRead(string $messageId): array
    {
        try {
            $response = $this->client->post($this->phoneNumberId . '/messages', [
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'status' => 'read',
                    'message_id' => $messageId,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Failed to mark message as read', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get message status
     */
    public function getMessageStatus(string $messageId): array
    {
        try {
            $response = $this->client->get($messageId);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get message status', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upload media file
     */
    public function uploadMedia(string $filePath, string $mimeType): array
    {
        try {
            $response = $this->client->post($this->phoneNumberId . '/media', [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($filePath, 'r'),
                        'filename' => basename($filePath),
                        'headers' => [
                            'Content-Type' => $mimeType,
                        ],
                    ],
                    [
                        'name' => 'messaging_product',
                        'contents' => 'whatsapp',
                    ],
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Failed to upload media', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Download media file
     */
    public function downloadMedia(string $mediaId): string
    {
        try {
            // First get media URL
            $response = $this->client->get($mediaId);
            $mediaData = json_decode($response->getBody()->getContents(), true);
            
            // Then download the file
            $response = $this->client->get($mediaData['url']);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            Log::error('Failed to download media', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get phone number info
     */
    public function getPhoneNumberInfo(): array
    {
        try {
            $response = $this->client->get($this->phoneNumberId);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get phone number info', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verify webhook
     */
    public function verifyWebhook(string $token, string $challenge): ?string
    {
        $verifyToken = Setting::get('whatsapp_webhook_verify_token');

        if ($token === $verifyToken) {
            return $challenge;
        }

        return null;
    }

    /**
     * Process incoming webhook payload
     */
    public function processWebhook(array $payload): void
    {
        if (isset($payload['entry'])) {
            foreach ($payload['entry'] as $entry) {
                if (isset($entry['changes'])) {
                    foreach ($entry['changes'] as $change) {
                        if ($change['field'] === 'messages') {
                            $this->processMessage($change['value']);
                        } elseif ($change['field'] === 'message_status') {
                            $this->processMessageStatus($change['value']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Process incoming message
     */
    protected function processMessage(array $data): void
    {
        if (!isset($data['messages'])) {
            return;
        }

        foreach ($data['messages'] as $messageData) {
            $contact = $this->findOrCreateContact($data);
            $conversation = $this->findOrCreateConversation($contact);
            
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'contact_id' => $contact->id,
                'direction' => 'inbound',
                'type' => $messageData['type'],
                'content' => $this->extractMessageContent($messageData),
                'media_url' => $this->extractMediaUrl($messageData),
                'whatsapp_message_id' => $messageData['id'],
                'status' => 'delivered',
                'sent_at' => now(),
                'metadata' => $messageData,
            ]);

            $conversation->updateLastMessage();
            $contact->updateLastMessage();

            // Trigger chatbot if active
            $this->triggerChatbot($contact, $message);
        }
    }

    /**
     * Process message status update
     */
    protected function processMessageStatus(array $data): void
    {
        if (!isset($data['statuses'])) {
            return;
        }

        foreach ($data['statuses'] as $statusData) {
            $message = Message::where('whatsapp_message_id', $statusData['id'])->first();
            
            if ($message) {
                switch ($statusData['status']) {
                    case 'sent':
                        $message->markAsSent();
                        break;
                    case 'delivered':
                        $message->markAsDelivered();
                        break;
                    case 'read':
                        $message->markAsRead();
                        break;
                    case 'failed':
                        $message->markAsFailed();
                        break;
                }
            }
        }
    }

    /**
     * Find or create contact from webhook data
     */
    protected function findOrCreateContact(array $data): Contact
    {
        $phone = str_replace('whatsapp:', '', $data['contacts'][0]['wa_id']);
        
        return Contact::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => $data['contacts'][0]['profile']['name'] ?? 'Unknown',
                'whatsapp_id' => $data['contacts'][0]['wa_id'],
                'status' => 'active',
            ]
        );
    }

    /**
     * Find or create conversation
     */
    protected function findOrCreateConversation(Contact $contact): Conversation
    {
        return Conversation::firstOrCreate(
            ['contact_id' => $contact->id, 'status' => 'open'],
            ['status' => 'open']
        );
    }

    /**
     * Extract message content based on type
     */
    protected function extractMessageContent(array $messageData): string
    {
        switch ($messageData['type']) {
            case 'text':
                return $messageData['text']['body'];
            case 'image':
            case 'video':
            case 'document':
                return $messageData[$messageData['type']]['caption'] ?? $messageData['type'] . ' message';
            case 'location':
                return "Location: {$messageData['location']['latitude']}, {$messageData['location']['longitude']}";
            case 'contact':
                return "Contact: {$messageData['contacts'][0]['name']['formatted_name']}";
            case 'interactive':
                if ($messageData['interactive']['type'] === 'button_reply') {
                    return $messageData['interactive']['button_reply']['title'];
                } elseif ($messageData['interactive']['type'] === 'list_reply') {
                    return $messageData['interactive']['list_reply']['title'];
                }
                return 'Interactive message';
            default:
                return $messageData['type'] . ' message';
        }
    }

    /**
     * Extract media URL from message data
     */
    protected function extractMediaUrl(array $messageData): ?array
    {
        $mediaTypes = ['image', 'video', 'document', 'audio'];
        
        foreach ($mediaTypes as $type) {
            if (isset($messageData[$type])) {
                return [$messageData[$type]['id']];
            }
        }

        return null;
    }

    /**
     * Trigger chatbot for incoming message
     */
    protected function triggerChatbot(Contact $contact, Message $message): void
    {
        // This will be implemented when we create the chatbot logic
        // For now, we'll just log it
        Log::info('Chatbot trigger', [
            'contact_id' => $contact->id,
            'message_id' => $message->id,
            'content' => $message->content,
        ]);
    }

    /**
     * Send message to WhatsApp API
     */
    protected function sendMessage(array $payload): array
    {
        try {
            $response = $this->client->post($this->phoneNumberId . '/messages', [
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            Log::info('Message sent successfully', [
                'payload' => $payload,
                'response' => $result,
            ]);

            return $result;
        } catch (RequestException $e) {
            Log::error('Failed to send message', [
                'payload' => $payload,
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);

            throw $e;
        }
    }

    /**
     * Check if API is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->accessToken) && !empty($this->phoneNumberId);
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $this->getPhoneNumberInfo();
            return true;
        } catch (RequestException $e) {
            return false;
        }
    }
}
