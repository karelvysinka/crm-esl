<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
    'user_id','title','source_type','mime','size','path','status','visibility','tags','error',
    'vectorized_at','embedding_provider','embedding_model','embedding_dim','vectors_count','last_index_duration_ms',
    ];

    protected $casts = [
        'tags' => 'array',
        'vectorized_at' => 'datetime',
    ];

    public function user(){ return $this->belongsTo(User::class); }
    public function chunks(){ return $this->hasMany(KnowledgeChunk::class, 'document_id'); }
}
