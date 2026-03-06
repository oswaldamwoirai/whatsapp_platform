<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    public static function set(string $key, $value, string $type = 'text'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }

    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
    }

    public static function getWhatsAppSettings(): array
    {
        return [
            'access_token' => static::get('whatsapp_access_token'),
            'phone_number_id' => static::get('whatsapp_phone_number_id'),
            'webhook_verify_token' => static::get('whatsapp_webhook_verify_token'),
            'api_version' => static::get('whatsapp_api_version', 'v18.0'),
        ];
    }

    public static function getSystemSettings(): array
    {
        return [
            'app_name' => static::get('app_name', 'WhatsApp Automation Platform'),
            'max_file_size' => static::get('max_file_size', 10240),
            'allowed_file_types' => static::get('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,mp3'),
            'rate_limit_per_minute' => static::get('rate_limit_per_minute', 60),
            'bulk_message_rate_limit' => static::get('bulk_message_rate_limit', 1000),
        ];
    }

    public static function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
            case 'json':
                return json_decode($value, true);
            case 'text':
            default:
                return $value;
        }
    }

    public function getValueAttribute($value)
    {
        return static::castValue($value, $this->type);
    }

    public function setValueAttribute($value)
    {
        if (in_array($this->type, ['array', 'json'])) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getFormattedValueAttribute(): string
    {
        switch ($this->type) {
            case 'boolean':
                return $this->value ? 'Yes' : 'No';
            case 'array':
            case 'json':
                return json_encode($this->value, JSON_PRETTY_PRINT);
            default:
                return (string) $this->value;
        }
    }

    public function isBoolean(): bool
    {
        return $this->type === 'boolean';
    }

    public function isNumeric(): bool
    {
        return in_array($this->type, ['integer', 'float']);
    }

    public function isArray(): bool
    {
        return in_array($this->type, ['array', 'json']);
    }

    public function getSelectOptions(): array
    {
        if ($this->isArray()) {
            return $this->options ?? [];
        }

        return [];
    }
}
