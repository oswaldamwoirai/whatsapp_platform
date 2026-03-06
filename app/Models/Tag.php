<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'description',
    ];

    protected $casts = [
        'color' => 'string',
    ];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_tags');
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_tags');
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function getContactCountAttribute(): int
    {
        return $this->contacts()->count();
    }

    public function getHexColorAttribute(): string
    {
        return $this->color ?: '#6B7280';
    }

    public function getRgbColorAttribute(): array
    {
        $hex = ltrim($this->hex_color, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }
}
