<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'chat_id',
        'name',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function conversations()
    {
        return $this->hasMany(WhatsappConversation::class, 'contact_id');
    }
}
