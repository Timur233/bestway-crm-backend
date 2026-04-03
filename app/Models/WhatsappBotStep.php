<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappBotStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'reply_text',
        'trigger_keywords',
        'options',
        'fallback_step_slug',
        'is_entry',
        'transfer_to_manager',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'trigger_keywords' => 'array',
        'options' => 'array',
        'is_entry' => 'boolean',
        'transfer_to_manager' => 'boolean',
        'is_active' => 'boolean',
    ];
}
