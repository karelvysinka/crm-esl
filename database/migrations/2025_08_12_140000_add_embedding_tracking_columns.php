<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->timestamp('vectorized_at')->nullable()->after('error');
            $table->string('embedding_provider', 50)->nullable()->after('vectorized_at');
            $table->string('embedding_model', 100)->nullable()->after('embedding_provider');
            $table->unsignedSmallInteger('embedding_dim')->nullable()->after('embedding_model');
            $table->unsignedInteger('vectors_count')->default(0)->after('embedding_dim');
            $table->unsignedInteger('last_index_duration_ms')->nullable()->after('vectors_count');
        });

        Schema::table('knowledge_chunks', function (Blueprint $table) {
            $table->unsignedSmallInteger('embedding_dim')->nullable()->after('embedding');
            $table->timestamp('embedded_at')->nullable()->after('embedding_dim');
            $table->string('qdrant_point_id', 100)->nullable()->after('embedded_at');
            $table->string('chunk_hash', 64)->nullable()->after('qdrant_point_id');
            $table->index(['document_id','chunk_hash']);
        });

        Schema::create('knowledge_embeddings_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained('knowledge_documents')->nullOnDelete();
            $table->foreignId('chunk_id')->nullable()->constrained('knowledge_chunks')->nullOnDelete();
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('status', 20)->default('started'); // started|done|failed
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['document_id']);
            $table->index(['status','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_embeddings_audit');
        Schema::table('knowledge_chunks', function (Blueprint $table) {
            $table->dropIndex(['document_id','chunk_hash']);
            $table->dropColumn(['embedding_dim','embedded_at','qdrant_point_id','chunk_hash']);
        });
        Schema::table('knowledge_documents', function (Blueprint $table) {
            $table->dropColumn(['vectorized_at','embedding_provider','embedding_model','embedding_dim','vectors_count','last_index_duration_ms']);
        });
    }
};
