<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatToolAudit extends Model
{
    protected $table = 'chat_tool_audit';
    public $timestamps = true;
    protected $fillable = [
        'user_id','conversation_id','tool','intent','payload','result_meta','duration_ms'
    ];

    protected $casts = [
        'payload' => 'array',
        'result_meta' => 'array',
    ];
}
