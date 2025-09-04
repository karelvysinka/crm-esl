<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    use HasFactory;

    protected $fillable = [
    'document_id','chunk_index','text','meta','embedding','embedding_dim','embedded_at','qdrant_point_id','chunk_hash',
    ];

    protected $casts = [
        'meta' => 'array',
        'embedding' => 'array',
    'embedded_at' => 'datetime',
    ];

    public function document(){ return $this->belongsTo(KnowledgeDocument::class, 'document_id'); }
}
