<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('request_templates');
            $table->foreignId('requester_id')->constrained('users');
            $table->string('title', 255);
            $table->json('content')->comment('양식에 맞는 입력 데이터');
            $table->enum('status', ['draft', 'submitted', 'pending', 'approved', 'rejected', 'canceled'])
                  ->default('draft');
            $table->unsignedInteger('current_step')->default(0)->comment('현재 결재 단계');
            $table->unsignedInteger('total_steps')->default(0)->comment('총 결재 단계 수');
            $table->enum('urgency', ['normal', 'urgent', 'critical'])->default('normal');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('requester_id');
            $table->index('status');
            $table->index('template_id');
            $table->index('submitted_at');
            $table->index(['requester_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
