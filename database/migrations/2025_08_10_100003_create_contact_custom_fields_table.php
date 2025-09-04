<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->nullable();
            $table->string('ac_field_id')->nullable();
            $table->timestamps();
            $table->index(['contact_id','key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_custom_fields');
    }
};
