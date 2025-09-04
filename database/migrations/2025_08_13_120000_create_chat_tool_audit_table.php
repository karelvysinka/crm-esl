<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_tool_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('tool', 64);
            $table->string('intent', 64)->nullable();
            $table->json('payload');
            $table->json('result_meta')->nullable();
            $table->integer('duration_ms')->default(0);
            $table->timestamps();
            $table->index(['tool','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_tool_audit');
    }
};
