<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
            $table->string('tool_name')->nullable();
            $table->json('inputs')->nullable();
            $table->json('outputs')->nullable();
            $table->string('status')->default('pending');
            $table->string('external_provider')->nullable();
            $table->string('external_execution_id')->nullable();
            $table->timestamps();
            $table->index(['session_id','message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_actions');
    }
};
