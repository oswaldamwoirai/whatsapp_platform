<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatbotFlow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'trigger_keywords',
        'status',
        'created_by',
    ];

    protected $casts = [
        'trigger_keywords' => 'array',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nodes()
    {
        return $this->hasMany(ChatbotNode::class, 'flow_id');
    }

    public function startNode()
    {
        return $this->nodes()->where('type', 'message')->whereNull('parent_id')->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function matchesTrigger(string $message): bool
    {
        $keywords = $this->trigger_keywords;
        
        foreach ($keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    public function getNodeByType(string $type)
    {
        return $this->nodes()->where('type', $type)->get();
    }

    public function getFlowStructure(): array
    {
        return $this->nodes()->with(['children'])->get()->toArray();
    }
}
