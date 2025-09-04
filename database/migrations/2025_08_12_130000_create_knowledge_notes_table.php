<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('knowledge_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('content');
            $table->json('tags')->nullable();
            $table->enum('visibility', ['public','private'])->default('public');
            $table->timestamps();
            $table->index(['visibility']);
            $table->index(['title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_notes');
    }
};
