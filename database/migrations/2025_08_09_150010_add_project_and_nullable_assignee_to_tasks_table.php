<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('priority')->constrained('projects')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }
};
