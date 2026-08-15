<?php

namespace App\Services\Import;

use App\Models\Office;
use Illuminate\Database\Eloquent\Model;

/**
 * Excelインポートウィザードにおける1エンティティ分の変換・検証・作成ロジックの契約。
 * ClientImportProcessor / ProcedureTaskImportProcessor が実装する。
 */
interface ImportProcessor
{
    /**
     * マッピング先として選べるシステム項目一覧。
     *
     * @return array<int, array{key: string, label: string, required: bool}>
     */
    public function fields(): array;

    /**
     * 生の行データ（列インデックス => セル値）を、列マッピング（列インデックス => フィールドkey）で
     * システム項目名をキーとした連想配列に変換する。氏名等での外部キー解決はここでは行わない。
     *
     * @param  array<int, mixed>  $rawRow
     * @param  array<int, string|null>  $columnMapping
     * @return array<string, mixed>
     */
    public function mapRow(array $rawRow, array $columnMapping): array;

    /**
     * マッピング済み行を検証する。氏名等による外部キーの名前解決もここで行い、
     * 解決結果（DBへそのまま渡せる形）をresolvedへ、確認画面表示用の人間可読な値をdisplayへ格納する。
     *
     * @param  array<string, mixed>  $mapped
     * @return array{errors: array<int, string>, warnings: array<int, string>, resolved: array<string, mixed>, display: array<int, array{label: string, value: string}>}
     */
    public function validateRow(array $mapped, Office $office): array;

    /**
     * @param  array<string, mixed>  $resolved
     */
    public function create(array $resolved): Model;
}
