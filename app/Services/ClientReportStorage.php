<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * client_reportsのPDF実体をs3ディスクへ保存・取得する窓口。
 * procedure_task_documentsと同じくバケットは非公開固定のため、参照は署名付きURL経由にする
 * （DocumentStorageと同じ方針、7.7章）。
 */
class ClientReportStorage
{
    private const TEMPORARY_URL_MINUTES = 5;

    public function store(int $officeId, string $pdfContents): string
    {
        $path = sprintf('client-reports/%d/%s.pdf', $officeId, Str::uuid());

        Storage::disk('s3')->put($path, $pdfContents);

        return $path;
    }

    public function temporaryUrl(string $path): string
    {
        $diskConfig = config('filesystems.disks.s3');
        $diskConfig['endpoint'] = $diskConfig['temporary_url_endpoint'] ?? $diskConfig['endpoint'];

        return Storage::build($diskConfig)->temporaryUrl($path, now()->addMinutes(self::TEMPORARY_URL_MINUTES));
    }
}
