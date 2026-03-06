<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'original_name',
        'mime_type',
        'size',
        'path',
        'disk',
        'metadata',
        'uploaded_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeByType($query, string $mimeType)
    {
        return $query->where('mime_type', 'like', "%{$mimeType}%");
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    public function scopeDocuments($query)
    {
        return $query->where('mime_type', 'like', 'application/%');
    }

    public function scopeAudios($query)
    {
        return $query->where('mime_type', 'like', 'audio/%');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isDocument(): bool
    {
        return str_starts_with($this->mime_type, 'application/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    public function getUrl(): string
    {
        return storage_path($this->path);
    }

    public function canBeUsedInWhatsApp(): bool
    {
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'video/mp4',
            'video/3gpp',
            'audio/mpeg',
            'audio/wav',
            'audio/ogg',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        return in_array($this->mime_type, $allowedTypes) && $this->size <= 16 * 1024 * 1024; // 16MB
    }
}
