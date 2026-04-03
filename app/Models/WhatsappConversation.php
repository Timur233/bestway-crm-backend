<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'manager_id',
        'status',
        'current_step_slug',
        'unresolved_count',
        'last_message_at',
        'manager_requested_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'manager_requested_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(WhatsappContact::class, 'contact_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
