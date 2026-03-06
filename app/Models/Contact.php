<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'whatsapp_id',
        'status',
        'metadata',
        'notes',
        'last_message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'contact_tags');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function activeConversation()
    {
        return $this->hasOne(Conversation::class)->whereIn('status', ['open', 'assigned']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByTag($query, $tagName)
    {
        return $query->whereHas('tags', function ($q) use ($tagName) {
            $q->where('name', $tagName);
        });
    }

    public function scopeWithActiveConversation($query)
    {
        return $query->whereHas('conversations', function ($q) {
            $q->whereIn('status', ['open', 'assigned']);
        });
    }

    public function addTag(Tag $tag): void
    {
        $this->tags()->syncWithoutDetaching($tag);
    }

    public function removeTag(Tag $tag): void
    {
        $this->tags()->detach($tag);
    }

    public function hasTag(string $tagName): bool
    {
        return $this->tags()->where('name', $tagName)->exists();
    }

    public function updateLastMessage(): void
    {
        $this->update(['last_message_at' => now()]);
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getFormattedPhoneAttribute(): string
    {
        return $this->phone;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
