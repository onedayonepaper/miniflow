<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('password')->constrained()->nullOnDelete();
            $table->string('position', 50)->nullable()->after('department_id')->comment('직책: 사원, 대리, 과장, 팀장, 부서장');
            $table->enum('role', ['user', 'approver', 'admin'])->default('user')->after('position');
            $table->softDeletes();

            $table->index('department_id');
            $table->index('role');
        });

        // Add manager foreign key to departments
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['department_id', 'position', 'role', 'deleted_at']);
        });
    }
};
