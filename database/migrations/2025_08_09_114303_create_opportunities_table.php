<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('value', 15, 2);
            $table->enum('stage', ['prospecting', 'qualification', 'proposal', 'negotiation', 'closing', 'won', 'lost'])->default('prospecting');
            $table->integer('probability')->default(10); // Percentage 0-100
            $table->date('expected_close_date');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->constrained('users');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->enum('close_reason', ['won', 'lost_to_competitor', 'lost_no_budget', 'lost_no_decision', 'lost_other'])->nullable();
            $table->text('close_notes')->nullable();
            $table->timestamps();
            
            $table->index(['stage', 'assigned_to']);
            $table->index(['expected_close_date', 'stage']);
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
