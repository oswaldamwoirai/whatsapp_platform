<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatbotNode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'flow_id',
        'name',
        'type',
        'config',
        'parent_id',
        'order',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function flow()
    {
        return $this->belongsTo(ChatbotFlow::class, 'flow_id');
    }

    public function parent()
    {
        return $this->belongsTo(ChatbotNode::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChatbotNode::class, 'parent_id')->orderBy('order');
    }

    public function siblings()
    {
        return $this->hasMany(ChatbotNode::class, 'parent_id', 'parent_id')
            ->where('id', '!=', $this->id)
            ->orderBy('order');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRootNodes($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function isMessage(): bool
    {
        return $this->type === 'message';
    }

    public function isCondition(): bool
    {
        return $this->type === 'condition';
    }

    public function isDelay(): bool
    {
        return $this->type === 'delay';
    }

    public function isAction(): bool
    {
        return $this->type === 'action';
    }

    public function isInput(): bool
    {
        return $this->type === 'input';
    }

    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function setConfigValue(string $key, $value): void
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
        $this->save();
    }

    public function getNextNode(?string $condition = null)
    {
        if ($this->isCondition()) {
            return $this->children()->where('config.condition', $condition)->first();
        }

        return $this->children()->first();
    }

    public function execute(array $context = []): array
    {
        switch ($this->type) {
            case 'message':
                return $this->executeMessage($context);
            case 'condition':
                return $this->executeCondition($context);
            case 'delay':
                return $this->executeDelay($context);
            case 'action':
                return $this->executeAction($context);
            case 'input':
                return $this->executeInput($context);
            default:
                return ['status' => 'error', 'message' => 'Unknown node type'];
        }
    }

    private function executeMessage(array $context): array
    {
        $message = $this->getConfigValue('message', '');
        $media = $this->getConfigValue('media', []);
        
        return [
            'status' => 'success',
            'type' => 'message',
            'content' => $message,
            'media' => $media,
        ];
    }

    private function executeCondition(array $context): array
    {
        $conditions = $this->getConfigValue('conditions', []);
        $userInput = $context['user_input'] ?? '';
        
        foreach ($conditions as $condition) {
            if (stripos($userInput, $condition['keyword']) !== false) {
                return [
                    'status' => 'success',
                    'type' => 'condition',
                    'matched' => $condition['keyword'],
                    'next_node' => $this->children()->where('config.condition', $condition['keyword'])->first(),
                ];
            }
        }

        return [
            'status' => 'success',
            'type' => 'condition',
            'matched' => null,
            'next_node' => $this->children()->where('config.condition', 'default')->first(),
        ];
    }

    private function executeDelay(array $context): array
    {
        $delay = $this->getConfigValue('delay', 1);
        
        return [
            'status' => 'success',
            'type' => 'delay',
            'delay' => $delay,
        ];
    }

    private function executeAction(array $context): array
    {
        $action = $this->getConfigValue('action', '');
        
        return [
            'status' => 'success',
            'type' => 'action',
            'action' => $action,
        ];
    }

    private function executeInput(array $context): array
    {
        $prompt = $this->getConfigValue('prompt', '');
        $variable = $this->getConfigValue('variable', 'input');
        
        return [
            'status' => 'success',
            'type' => 'input',
            'prompt' => $prompt,
            'variable' => $variable,
        ];
    }
}
