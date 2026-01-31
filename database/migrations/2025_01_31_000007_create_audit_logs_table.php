<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->comment('NULL이면 시스템');
            $table->string('action', 50)->comment('create, update, delete, approve, reject, submit...');
            $table->string('target_type', 100)->comment('App\\Models\\ApprovalRequest 등');
            $table->unsignedBigInteger('target_id');
            $table->json('changes')->nullable()->comment('변경 내역 {before: {}, after: {}}');
            $table->json('metadata')->nullable()->comment('추가 정보');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index(['target_type', 'target_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
