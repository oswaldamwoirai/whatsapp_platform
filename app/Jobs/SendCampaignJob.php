<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Conversation;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Campaign $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
        $this->onQueue('campaigns');
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsappService): void
    {
        try {
            Log::info('Starting campaign job', ['campaign_id' => $this->campaign->id]);

            // Get target contacts
            $contacts = $this->getTargetContacts();

            if ($contacts->isEmpty()) {
                Log::warning('No target contacts found for campaign', ['campaign_id' => $this->campaign->id]);
                $this->campaign->complete();
                return;
            }

            $sentCount = 0;
            $failedCount = 0;

            foreach ($contacts as $index => $contact) {
                try {
                    // Create conversation if needed
                    $conversation = $contact->activeConversation() ?? Conversation::create([
                        'contact_id' => $contact->id,
                        'status' => 'open',
                    ]);

                    // Create message record
                    $message = Message::create([
                        'conversation_id' => $conversation->id,
                        'contact_id' => $contact->id,
                        'user_id' => $this->campaign->created_by,
                        'direction' => 'outbound',
                        'type' => $this->campaign->type,
                        'content' => $this->campaign->message,
                        'status' => 'pending',
                        'metadata' => [
                            'campaign_id' => $this->campaign->id,
                        ],
                    ]);

                    // Send message via WhatsApp
                    $this->sendMessage($whatsappService, $contact, $message);

                    $sentCount++;
                    $this->campaign->incrementSent();

                    // Add delay between messages to respect rate limits
                    if ($index < $contacts->count() - 1) {
                        $this->release(1); // Wait 1 second between messages
                        return;
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to send campaign message', [
                        'campaign_id' => $this->campaign->id,
                        'contact_id' => $contact->id,
                        'error' => $e->getMessage(),
                    ]);

                    $failedCount++;
                    $this->campaign->incrementFailed();
                }
            }

            // Mark campaign as completed
            $this->campaign->complete();

            Log::info('Campaign completed', [
                'campaign_id' => $this->campaign->id,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign job failed', [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
            ]);

            $this->campaign->cancel();
            throw $e;
        }
    }

    /**
     * Get target contacts for the campaign
     */
    protected function getTargetContacts(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Contact::active();

        // Filter by campaign tags
        if ($this->campaign->target_tags) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('name', $this->campaign->target_tags);
            });
        }

        // Filter by specific contacts
        if ($this->campaign->target_contacts) {
            $query->whereIn('id', $this->campaign->target_contacts);
        }

        return $query->get();
    }

    /**
     * Send individual message
     */
    protected function sendMessage(WhatsAppService $whatsappService, Contact $contact, Message $message): void
    {
        switch ($this->campaign->type) {
            case 'text':
                $response = $whatsappService->sendTextMessage($contact->phone, $this->campaign->message);
                break;

            case 'template':
                // This would use template name from campaign metadata
                $templateName = $this->campaign->metadata['template_name'] ?? 'default_template';
                $response = $whatsappService->sendTemplateMessage($contact->phone, $templateName);
                break;

            case 'media':
                // This would use media URL from campaign metadata
                $mediaUrl = $this->campaign->metadata['media_url'] ?? '';
                $response = $whatsappService->sendImageMessage($contact->phone, $mediaUrl, $this->campaign->message);
                break;

            default:
                throw new \InvalidArgumentException("Unsupported campaign type: {$this->campaign->type}");
        }

        // Update message with WhatsApp ID
        if (isset($response['messages'][0]['id'])) {
            $message->update([
                'whatsapp_message_id' => $response['messages'][0]['id'],
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $this->campaign->incrementSent();
        } else {
            $message->markAsFailed();
            $this->campaign->incrementFailed();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Campaign job failed permanently', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);

        $this->campaign->cancel();
    }
}
