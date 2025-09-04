<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->string('source_type', 20)->default('upload'); // upload|url|note
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('path', 500)->nullable();
            $table->enum('status', ['queued','processing','ready','failed'])->default('queued');
            $table->enum('visibility', ['public','private'])->default('public');
            $table->json('tags')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['status']);
            $table->index(['visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_documents');
    }
};
