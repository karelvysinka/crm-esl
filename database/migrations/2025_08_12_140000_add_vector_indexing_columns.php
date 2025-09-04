<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_documents', 'vectorized_at')) {
                $table->timestamp('vectorized_at')->nullable()->after('status');
            }
        });
        Schema::table('knowledge_chunks', function (Blueprint $table) {
            if (!Schema::hasColumn('knowledge_chunks', 'embedding_dim')) {
                $table->unsignedInteger('embedding_dim')->nullable()->after('text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_documents', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_documents', 'vectorized_at')) {
                $table->dropColumn('vectorized_at');
            }
        });
        Schema::table('knowledge_chunks', function (Blueprint $table) {
            if (Schema::hasColumn('knowledge_chunks', 'embedding_dim')) {
                $table->dropColumn('embedding_dim');
            }
        });
    }
};
