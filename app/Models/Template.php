<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'components',
        'language',
        'status',
        'whatsapp_template_id',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'components' => 'array',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    public function approve(string $whatsappId): void
    {
        $this->update([
            'status' => 'approved',
            'whatsapp_template_id' => $whatsappId,
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function submit(): void
    {
        $this->update(['status' => 'pending']);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeUsed(): bool
    {
        return $this->isApproved();
    }

    public function getHeaderComponent(): ?array
    {
        return collect($this->components)->firstWhere('type', 'HEADER');
    }

    public function getBodyComponent(): ?array
    {
        return collect($this->components)->firstWhere('type', 'BODY');
    }

    public function getFooterComponent(): ?array
    {
        return collect($this->components)->firstWhere('type', 'FOOTER');
    }

    public function getButtonsComponent(): ?array
    {
        return collect($this->components)->firstWhere('type', 'BUTTONS');
    }

    public function getBodyText(): string
    {
        $body = $this->getBodyComponent();
        return $body['text'] ?? '';
    }

    public function getHeaderType(): ?string
    {
        $header = $this->getHeaderComponent();
        return $header['format'] ?? null;
    }

    public function hasHeader(): bool
    {
        return !is_null($this->getHeaderComponent());
    }

    public function hasFooter(): bool
    {
        return !is_null($this->getFooterComponent());
    }

    public function hasButtons(): bool
    {
        return !is_null($this->getButtonsComponent());
    }

    public function getButtonCount(): int
    {
        $buttons = $this->getButtonsComponent();
        return count($buttons['buttons'] ?? []);
    }

    public function getVariableCount(): int
    {
        $body = $this->getBodyText();
        preg_match_all('/{{\d+}}/', $body, $matches);
        return count($matches[0]);
    }
}
