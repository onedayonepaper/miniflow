<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Resources\AttachmentResource;
use App\Models\ApprovalRequest;
use App\Models\Attachment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        $this->authorize('update', $approvalRequest);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('attachments/' . $approvalRequest->id, $filename);

        $attachment = Attachment::create([
            'request_id' => $approvalRequest->id,
            'uploader_id' => $request->user()->id,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
        ]);

        return $this->created(
            new AttachmentResource($attachment->load('uploader')),
            '파일이 업로드되었습니다.'
        );
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment->request);

        if (!$attachment->exists()) {
            throw ApiException::notFound('파일을 찾을 수 없습니다.');
        }

        return Storage::download(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        $this->authorize('update', $attachment->request);

        if (!$attachment->request->canEdit()) {
            throw ApiException::conflict('임시저장 상태에서만 첨부파일을 삭제할 수 있습니다.');
        }

        Storage::delete($attachment->path);
        $attachment->delete();

        return $this->success(message: '첨부파일이 삭제되었습니다.');
    }
}
