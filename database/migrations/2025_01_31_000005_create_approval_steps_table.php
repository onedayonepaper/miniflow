<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users');
            $table->unsignedInteger('step_order')->comment('승인 순서 (1, 2, 3...)');
            $table->enum('type', ['approve', 'review', 'notify'])->default('approve')
                  ->comment('approve:승인필요, review:검토, notify:참조');
            $table->enum('status', ['waiting', 'pending', 'approved', 'rejected', 'skipped'])
                  ->default('waiting');
            $table->text('comment')->nullable()->comment('승인 의견');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('due_date')->nullable()->comment('승인 기한');
            $table->timestamps();

            $table->index('request_id');
            $table->index('approver_id');
            $table->index('status');
            $table->index(['request_id', 'step_order']);
            $table->index(['approver_id', 'status']);
            $table->unique(['request_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
