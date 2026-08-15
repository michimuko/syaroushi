<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * procedure_task_documentsのファイル実体（特定個人情報を含み得る）をs3ディスクへ
 * 保存・取得するための窓口（企画書7.7章）。バケットは非公開固定のため、
 * 参照は必ず署名付きURL（短い有効期限）経由にする。
 */
class DocumentStorage
{
    private const TEMPORARY_URL_MINUTES = 5;

    public function store(int $officeId, UploadedFile $file): string
    {
        $path = sprintf('procedure-task-documents/%d/%s.%s', $officeId, Str::uuid(), $file->getClientOriginalExtension());

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function temporaryUrl(string $path): string
    {
        $diskConfig = config('filesystems.disks.s3');
        $diskConfig['endpoint'] = $diskConfig['temporary_url_endpoint'] ?? $diskConfig['endpoint'];

        return Storage::build($diskConfig)->temporaryUrl($path, now()->addMinutes(self::TEMPORARY_URL_MINUTES));
    }

    public function delete(string $path): void
    {
        Storage::disk('s3')->delete($path);
    }
}
