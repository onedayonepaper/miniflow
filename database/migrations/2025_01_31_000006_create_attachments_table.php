<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignId('uploader_id')->constrained('users');
            $table->string('filename', 255)->comment('저장된 파일명 (UUID)');
            $table->string('original_name', 255)->comment('원본 파일명');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size')->comment('파일 크기 (bytes)');
            $table->string('path', 500)->comment('저장 경로');
            $table->timestamp('created_at')->useCurrent();

            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
