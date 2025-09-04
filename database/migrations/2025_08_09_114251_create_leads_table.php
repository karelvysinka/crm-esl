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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->enum('source', ['website', 'referral', 'social_media', 'cold_call', 'email_campaign', 'trade_show', 'other'])->default('website');
            $table->enum('status', ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost'])->default('new');
            $table->integer('score')->default(0); // Lead scoring 0-100
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('converted_at')->nullable(); // Converted to opportunity
            $table->unsignedBigInteger('converted_to_opportunity_id')->nullable(); // Will add foreign key later
            $table->timestamps();
            
            $table->index(['status', 'assigned_to']);
            $table->index(['source', 'status']);
            $table->index('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
