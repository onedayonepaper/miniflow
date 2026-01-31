<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 50)->comment('leave, expense, account, etc.');
            $table->text('description')->nullable();
            $table->json('schema')->comment('폼 필드 정의');
            $table->json('default_approval_line')->nullable()->comment('기본 결재선 설정');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_templates');
    }
};
