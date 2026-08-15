<?php

namespace App\Console\Commands\Imports;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Excelインポートウィザードの一時ファイル（imports/{office_id}/{token}）のうち、
 * アップロードされたままウィザードが完了しなかったものを削除する。
 * 個人情報を含み得るため、確定(commit)時に削除されなかったものが無期限に残らないようにする
 * （企画書7.7章の個人情報保護方針に準じる）。
 */
#[Signature('imports:cleanup-stale-files')]
#[Description('Excelインポートウィザードで確定されずに残った一時ファイルを削除する')]
class CleanupStaleImportFilesCommand extends Command
{
    private const STALE_AFTER_HOURS = 24;

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $threshold = now()->subHours(self::STALE_AFTER_HOURS)->getTimestamp();

        $deletedCount = 0;

        foreach ($disk->allFiles('imports') as $path) {
            if ($disk->lastModified($path) < $threshold) {
                $disk->delete($path);
                $deletedCount++;
            }
        }

        $this->info("{$deletedCount}件の一時ファイルを削除しました。");

        return self::SUCCESS;
    }
}
