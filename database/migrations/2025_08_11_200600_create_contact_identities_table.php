<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('source'); // e.g. 'ac'
            $table->string('external_id');
            $table->string('external_hash')->nullable();
            $table->timestamps();

            $table->unique(['source','external_id']);
            $table->index(['contact_id','source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_identities');
    }
};
