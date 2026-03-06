<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'message',
        'type',
        'status',
        'target_tags',
        'target_contacts',
        'total_contacts',
        'sent_count',
        'delivered_count',
        'read_count',
        'failed_count',
        'scheduled_at',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'target_tags' => 'array',
        'target_contacts' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'campaign_tags');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSending($query)
    {
        return $query->where('status', 'sending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function start(): void
    {
        $this->update([
            'status' => 'sending',
            'started_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    public function incrementSent(): void
    {
        $this->increment('sent_count');
    }

    public function incrementDelivered(): void
    {
        $this->increment('delivered_count');
    }

    public function incrementRead(): void
    {
        $this->increment('read_count');
    }

    public function incrementFailed(): void
    {
        $this->increment('failed_count');
    }

    public function getDeliveryRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return ($this->delivered_count / $this->sent_count) * 100;
    }

    public function getReadRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }

        return ($this->read_count / $this->delivered_count) * 100;
    }

    public function getFailureRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return ($this->failed_count / $this->sent_count) * 100;
    }

    public function getCompletionPercentageAttribute(): float
    {
        if ($this->total_contacts === 0) {
            return 0;
        }

        return ($this->sent_count / $this->total_contacts) * 100;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeStarted(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'sending']);
    }
}
