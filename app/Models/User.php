<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function assignedConversations()
    {
        return $this->hasMany(Conversation::class, 'assigned_to');
    }

    public function chatbotFlows()
    {
        return $this->hasMany(ChatbotFlow::class, 'created_by');
    }

    public function templates()
    {
        return $this->hasMany(Template::class, 'created_by');
    }

    public function mediaFiles()
    {
        return $this->hasMany(MediaFile::class, 'uploaded_by');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function canManageContacts(): bool
    {
        return $this->hasPermissionTo('manage-contacts');
    }

    public function canManageCampaigns(): bool
    {
        return $this->hasPermissionTo('manage-campaigns');
    }

    public function canManageChatbotFlows(): bool
    {
        return $this->hasPermissionTo('manage-chatbot-flows');
    }

    public function canManageIntegrations(): bool
    {
        return $this->hasPermissionTo('manage-integrations');
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermissionTo('manage-users');
    }
}
