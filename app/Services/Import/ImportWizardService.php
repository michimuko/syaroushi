<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Excelインポートウィザード（アップロード→マッピング→確認→確定）の間だけ使う
 * 一時ファイルを事務所単位のローカルディスクへ保存・解決・削除する窓口。
 * officeIdは常に呼び出し元がAuth::user()->office_idから渡す（クライアント入力を信用しない）ため、
 * tokenだけが渡っても他事務所のファイルへは構造的にアクセスできない。
 * 個人情報を含み得るため、確定(commit)時に必ず削除する。ウィザード離脱時の残留対策は
 * imports:cleanup-stale-filesバッチ（Console\Commands\Imports）を参照。
 */
class ImportWizardService
{
    private const DISK = 'local';

    public function storeTempFile(int $officeId, UploadedFile $file): string
    {
        $token = (string) Str::uuid();

        Storage::disk(self::DISK)->putFileAs($this->directory($officeId), $file, $token);

        return $token;
    }

    /**
     * $tokenはコントローラ側で'uuid'バリデーションルールを通した値のみを渡すこと
     * （パストラバーサル対策。この層では形式チェックをしない）。
     */
    public function resolvePath(int $officeId, string $token): string
    {
        $path = $this->relativePath($officeId, $token);

        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->path($path);
    }

    public function deleteTempFile(int $officeId, string $token): void
    {
        Storage::disk(self::DISK)->delete($this->relativePath($officeId, $token));
    }

    private function relativePath(int $officeId, string $token): string
    {
        return $this->directory($officeId).'/'.$token;
    }

    private function directory(int $officeId): string
    {
        return sprintf('imports/%d', $officeId);
    }
}
