<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_id',
        'assigned_to',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function unreadMessages()
    {
        return $this->messages()->where('direction', 'inbound')->where('read_at', null);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereIn('status', ['open', 'resolved'])->whereNull('assigned_to');
    }

    public function assignTo(User $user): void
    {
        $this->update([
            'assigned_to' => $user->id,
            'status' => 'assigned',
        ]);
    }

    public function resolve(): void
    {
        $this->update(['status' => 'resolved']);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function reopen(): void
    {
        $this->update(['status' => 'open']);
    }

    public function updateLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    public function getUnreadCountAttribute(): int
    {
        return $this->unreadMessages()->count();
    }

    public function isAssigned(): bool
    {
        return !is_null($this->assigned_to);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function canBeAssigned(): bool
    {
        return in_array($this->status, ['open', 'resolved']);
    }

    public function canBeResolved(): bool
    {
        return in_array($this->status, ['open', 'assigned']);
    }
}
