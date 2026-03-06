<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Jobs\SendCampaignJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /**
     * Display a listing of the campaigns.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Campaign::with(['createdBy', 'tags']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by creator
        if ($request->has('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $campaigns = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => ['required', Rule::in(['text', 'template', 'media'])],
            'target_tags' => 'nullable|array',
            'target_tags.*' => 'exists:tags,name',
            'target_contacts' => 'nullable|array',
            'target_contacts.*' => 'exists:contacts,id',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // Get target contacts
        $contacts = $this->getTargetContacts($validated);
        
        $validated['total_contacts'] = $contacts->count();
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'draft';

        $campaign = Campaign::create($validated);

        // Associate tags if provided
        if (!empty($validated['target_tags'])) {
            $tagIds = \App\Models\Tag::whereIn('name', $validated['target_tags'])->pluck('id');
            $campaign->tags()->sync($tagIds);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign->load(['createdBy', 'tags']),
            'message' => 'Campaign created successfully',
        ], 201);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign): JsonResponse
    {
        $campaign->load(['createdBy', 'tags', 'messages']);

        return response()->json([
            'success' => true,
            'data' => $campaign,
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string',
            'type' => ['sometimes', 'required', Rule::in(['text', 'template', 'media'])],
            'target_tags' => 'nullable|array',
            'target_tags.*' => 'exists:tags,name',
            'target_contacts' => 'nullable|array',
            'target_contacts.*' => 'exists:contacts,id',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        // Only allow updates if campaign is in draft status
        if (!$campaign->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft campaigns can be updated',
            ], 400);
        }

        // Recalculate target contacts if targeting changed
        if (isset($validated['target_tags']) || isset($validated['target_contacts'])) {
            $contacts = $this->getTargetContacts($validated);
            $validated['total_contacts'] = $contacts->count();
        }

        $campaign->update($validated);

        // Update tag associations
        if (isset($validated['target_tags'])) {
            $tagIds = \App\Models\Tag::whereIn('name', $validated['target_tags'])->pluck('id');
            $campaign->tags()->sync($tagIds);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign->load(['createdBy', 'tags']),
            'message' => 'Campaign updated successfully',
        ]);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(Campaign $campaign): JsonResponse
    {
        // Only allow deletion of draft campaigns
        if (!$campaign->isDraft()) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft campaigns can be deleted',
            ], 400);
        }

        $campaign->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully',
        ]);
    }

    /**
     * Send campaign immediately
     */
    public function send(Campaign $campaign): JsonResponse
    {
        if (!$campaign->canBeStarted()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be started',
            ], 400);
        }

        try {
            $campaign->start();
            
            // Dispatch job to send messages
            SendCampaignJob::dispatch($campaign);

            return response()->json([
                'success' => true,
                'data' => $campaign->load(['createdBy', 'tags']),
                'message' => 'Campaign sending started',
            ]);
        } catch (\Exception $e) {
            $campaign->cancel();

            return response()->json([
                'success' => false,
                'message' => 'Failed to start campaign: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Schedule campaign for later sending
     */
    public function schedule(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        if (!$campaign->canBeStarted()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be scheduled',
            ], 400);
        }

        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $campaign->load(['createdBy', 'tags']),
            'message' => 'Campaign scheduled successfully',
        ]);
    }

    /**
     * Cancel campaign
     */
    public function cancel(Campaign $campaign): JsonResponse
    {
        if (!$campaign->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign cannot be cancelled',
            ], 400);
        }

        $campaign->cancel();

        return response()->json([
            'success' => true,
            'data' => $campaign->load(['createdBy', 'tags']),
            'message' => 'Campaign cancelled successfully',
        ]);
    }

    /**
     * Get campaign analytics
     */
    public function analytics(Campaign $campaign): JsonResponse
    {
        $analytics = [
            'campaign' => $campaign->load(['createdBy', 'tags']),
            'statistics' => [
                'total_contacts' => $campaign->total_contacts,
                'sent_count' => $campaign->sent_count,
                'delivered_count' => $campaign->delivered_count,
                'read_count' => $campaign->read_count,
                'failed_count' => $campaign->failed_count,
                'delivery_rate' => $campaign->delivery_rate,
                'read_rate' => $campaign->read_rate,
                'failure_rate' => $campaign->failure_rate,
                'completion_percentage' => $campaign->completion_percentage,
            ],
            'timeline' => $this->getCampaignTimeline($campaign),
            'performance' => $this->getCampaignPerformance($campaign),
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }

    /**
     * Get campaign statistics summary
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_campaigns' => Campaign::count(),
            'draft_campaigns' => Campaign::draft()->count(),
            'scheduled_campaigns' => Campaign::scheduled()->count(),
            'sending_campaigns' => Campaign::sending()->count(),
            'completed_campaigns' => Campaign::completed()->count(),
            'cancelled_campaigns' => Campaign::cancelled()->count(),
            'total_messages_sent' => Campaign::sum('sent_count'),
            'total_delivered' => Campaign::sum('delivered_count'),
            'total_read' => Campaign::sum('read_count'),
            'total_failed' => Campaign::sum('failed_count'),
            'recent_campaigns' => Campaign::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get target contacts for campaign
     */
    protected function getTargetContacts(array $data): \Illuminate\Database\Eloquent\Collection
    {
        $query = Contact::active();

        // Filter by tags
        if (!empty($data['target_tags'])) {
            $query->whereHas('tags', function ($q) use ($data) {
                $q->whereIn('name', $data['target_tags']);
            });
        }

        // Filter by specific contacts
        if (!empty($data['target_contacts'])) {
            $query->whereIn('id', $data['target_contacts']);
        }

        return $query->get();
    }

    /**
     * Get campaign timeline data
     */
    protected function getCampaignTimeline(Campaign $campaign): array
    {
        $timeline = [];

        if ($campaign->created_at) {
            $timeline[] = [
                'event' => 'Created',
                'timestamp' => $campaign->created_at->format('Y-m-d H:i:s'),
                'user' => $campaign->createdBy->name,
            ];
        }

        if ($campaign->scheduled_at) {
            $timeline[] = [
                'event' => 'Scheduled',
                'timestamp' => $campaign->scheduled_at->format('Y-m-d H:i:s'),
                'user' => $campaign->createdBy->name,
            ];
        }

        if ($campaign->started_at) {
            $timeline[] = [
                'event' => 'Started',
                'timestamp' => $campaign->started_at->format('Y-m-d H:i:s'),
                'user' => $campaign->createdBy->name,
            ];
        }

        if ($campaign->completed_at) {
            $timeline[] = [
                'event' => 'Completed',
                'timestamp' => $campaign->completed_at->format('Y-m-d H:i:s'),
                'user' => $campaign->createdBy->name,
            ];
        }

        return $timeline;
    }

    /**
     * Get campaign performance metrics
     */
    protected function getCampaignPerformance(Campaign $campaign): array
    {
        return [
            'average_delivery_time' => $this->calculateAverageDeliveryTime($campaign),
            'peak_delivery_hour' => $this->getPeakDeliveryHour($campaign),
            'best_performing_contacts' => $this->getBestPerformingContacts($campaign),
            'engagement_rate' => $campaign->total_contacts > 0 
                ? ($campaign->read_count / $campaign->total_contacts) * 100 
                : 0,
        ];
    }

    /**
     * Calculate average delivery time
     */
    protected function calculateAverageDeliveryTime(Campaign $campaign): string
    {
        // This would be calculated from actual message timestamps
        // For now, return a placeholder
        return '2.5 minutes';
    }

    /**
     * Get peak delivery hour
     */
    protected function getPeakDeliveryHour(Campaign $campaign): int
    {
        // This would be calculated from actual message timestamps
        // For now, return a placeholder
        return 14; // 2 PM
    }

    /**
     * Get best performing contacts
     */
    protected function getBestPerformingContacts(Campaign $campaign): array
    {
        // This would identify contacts with highest engagement
        // For now, return a placeholder
        return [
            ['contact_name' => 'John Doe', 'engagement' => 95],
            ['contact_name' => 'Jane Smith', 'engagement' => 87],
        ];
    }
}
