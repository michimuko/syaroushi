<?php

namespace App\Services\Import;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Office;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * 顧問先（Client）のExcelインポート処理（Excel移行アシスタント、企画書6章）。
 */
class ClientImportProcessor implements ImportProcessor
{
    public function fields(): array
    {
        return [
            ['key' => 'name', 'label' => '顧問先名', 'required' => true],
            ['key' => 'representative_name', 'label' => '代表者', 'required' => false],
            ['key' => 'address', 'label' => '住所', 'required' => false],
            ['key' => 'phone', 'label' => '電話番号', 'required' => false],
            ['key' => 'email', 'label' => 'メールアドレス', 'required' => false],
            ['key' => 'contract_start_date', 'label' => '契約開始日', 'required' => false],
            ['key' => 'status', 'label' => 'ステータス（契約中 / 契約終了）', 'required' => false],
            ['key' => 'assigned_user_name', 'label' => '担当者名（事務所内スタッフの氏名と完全一致）', 'required' => false],
            ['key' => 'notes', 'label' => 'メモ', 'required' => false],
        ];
    }

    public function mapRow(array $rawRow, array $columnMapping): array
    {
        $mapped = [];

        foreach ($columnMapping as $columnIndex => $fieldKey) {
            if ($fieldKey === null) {
                continue;
            }

            $value = $rawRow[$columnIndex] ?? null;
            $mapped[$fieldKey] = is_string($value) ? trim($value) : $value;
        }

        return $mapped;
    }

    public function validateRow(array $mapped, Office $office): array
    {
        $errors = [];
        $warnings = [];

        $assignedUserId = null;
        $assignedUserName = trim((string) ($mapped['assigned_user_name'] ?? ''));
        if ($assignedUserName !== '') {
            $assignedUser = User::query()
                ->where('office_id', $office->id)
                ->where('name', $assignedUserName)
                ->first();

            if ($assignedUser) {
                $assignedUserId = $assignedUser->id;
            } else {
                $errors[] = "担当者「{$assignedUserName}」が見つかりません。";
            }
        }

        $status = ClientStatus::Active;
        $statusInput = trim((string) ($mapped['status'] ?? ''));
        if ($statusInput !== '') {
            $resolvedStatus = ClientStatus::tryFrom($statusInput) ?? ClientStatus::fromLabel($statusInput);
            if ($resolvedStatus === null) {
                $errors[] = "ステータス「{$statusInput}」は認識できません（契約中/契約終了のいずれかを指定してください）。";
            } else {
                $status = $resolvedStatus;
            }
        }

        $contractStartDate = null;
        $dateInput = trim((string) ($mapped['contract_start_date'] ?? ''));
        if ($dateInput !== '') {
            try {
                $contractStartDate = Carbon::parse($dateInput)->toDateString();
            } catch (Throwable) {
                $errors[] = "契約開始日「{$dateInput}」を日付として解釈できません。";
            }
        }

        $notes = ($mapped['notes'] ?? null) ?: null;
        if ($notes && MyNumberDetector::containsMyNumberLikeString($notes)) {
            $warnings[] = 'メモ欄にマイナンバーらしき文字列が含まれています。';
        }

        $resolved = [
            'name' => $mapped['name'] ?? null,
            'representative_name' => ($mapped['representative_name'] ?? null) ?: null,
            'address' => ($mapped['address'] ?? null) ?: null,
            'phone' => ($mapped['phone'] ?? null) ?: null,
            'email' => ($mapped['email'] ?? null) ?: null,
            'contract_start_date' => $contractStartDate,
            'status' => $status,
            'assigned_user_id' => $assignedUserId,
            'notes' => $notes,
        ];

        $validator = Validator::make($resolved, [
            'name' => 'required|string|max:255',
            'representative_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        foreach ($validator->errors()->all() as $message) {
            $errors[] = $message;
        }

        $display = [
            ['label' => '顧問先名', 'value' => (string) ($resolved['name'] ?? '')],
            ['label' => '代表者', 'value' => (string) ($resolved['representative_name'] ?? '')],
            ['label' => '住所', 'value' => (string) ($resolved['address'] ?? '')],
            ['label' => '電話番号', 'value' => (string) ($resolved['phone'] ?? '')],
            ['label' => 'メールアドレス', 'value' => (string) ($resolved['email'] ?? '')],
            ['label' => '契約開始日', 'value' => (string) ($resolved['contract_start_date'] ?? '')],
            ['label' => 'ステータス', 'value' => $status->label()],
            ['label' => '担当者', 'value' => $assignedUserName],
            ['label' => 'メモ', 'value' => (string) ($notes ?? '')],
        ];

        return ['errors' => $errors, 'warnings' => $warnings, 'resolved' => $resolved, 'display' => $display];
    }

    public function create(array $resolved): Client
    {
        return Client::create($resolved);
    }
}
