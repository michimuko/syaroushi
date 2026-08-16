<?php

namespace App\Services\Import;

use App\Enums\CustomFieldTarget;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * 手続きタスク（ProcedureTask）のExcelインポート処理（Excel移行アシスタント、企画書6章）。
 */
class ProcedureTaskImportProcessor implements ImportProcessor
{
    use ResolvesCustomFieldsForImport;

    public function fields(Office $office): array
    {
        return [
            ['key' => 'client_name', 'label' => '顧問先名（事務所内の顧問先名と完全一致）', 'required' => true],
            ['key' => 'procedure_type_name', 'label' => '手続き種別名（有効な手続き種別のみ）', 'required' => true],
            ['key' => 'title', 'label' => 'タイトル', 'required' => true],
            ['key' => 'due_date', 'label' => '期限日', 'required' => true],
            ['key' => 'status', 'label' => 'ステータス（未着手/進行中/書類収集済/提出済/完了）', 'required' => false],
            ['key' => 'assigned_user_name', 'label' => '担当者名（事務所内スタッフの氏名と完全一致）', 'required' => false],
            ['key' => 'notes', 'label' => 'メモ', 'required' => false],
            ...$this->customFieldMappingFields($this->customFieldDefinitions($office, CustomFieldTarget::ProcedureTask)),
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

        $clientId = null;
        $clientName = trim((string) ($mapped['client_name'] ?? ''));
        if ($clientName === '') {
            $errors[] = '顧問先名は必須です。';
        } else {
            $client = Client::query()->where('office_id', $office->id)->where('name', $clientName)->first();
            if ($client) {
                $clientId = $client->id;
            } else {
                $errors[] = "顧問先「{$clientName}」が見つかりません。";
            }
        }

        $procedureTypeId = null;
        $procedureTypeName = trim((string) ($mapped['procedure_type_name'] ?? ''));
        if ($procedureTypeName === '') {
            $errors[] = '手続き種別名は必須です。';
        } else {
            $procedureType = ProcedureType::query()
                ->where('is_active', true)
                ->where('name', $procedureTypeName)
                ->first();
            if ($procedureType) {
                $procedureTypeId = $procedureType->id;
            } else {
                $errors[] = "手続き種別「{$procedureTypeName}」が見つかりません（有効な手続き種別のみ対象です）。";
            }
        }

        $assignedUserId = null;
        $assignedUserName = trim((string) ($mapped['assigned_user_name'] ?? ''));
        if ($assignedUserName !== '') {
            $assignedUser = User::query()->where('office_id', $office->id)->where('name', $assignedUserName)->first();
            if ($assignedUser) {
                $assignedUserId = $assignedUser->id;
            } else {
                $errors[] = "担当者「{$assignedUserName}」が見つかりません。";
            }
        }

        $status = TaskStatus::NotStarted;
        $statusInput = trim((string) ($mapped['status'] ?? ''));
        if ($statusInput !== '') {
            $resolvedStatus = TaskStatus::tryFrom($statusInput) ?? TaskStatus::fromLabel($statusInput);
            if ($resolvedStatus === null) {
                $errors[] = "ステータス「{$statusInput}」は認識できません（未着手/進行中/書類収集済/提出済/完了のいずれかを指定してください）。";
            } else {
                $status = $resolvedStatus;
            }
        }

        $dueDate = null;
        $dueDateInput = trim((string) ($mapped['due_date'] ?? ''));
        if ($dueDateInput === '') {
            $errors[] = '期限日は必須です。';
        } else {
            try {
                $dueDate = Carbon::parse($dueDateInput)->toDateString();
            } catch (Throwable) {
                $errors[] = "期限日「{$dueDateInput}」を日付として解釈できません。";
            }
        }

        $notes = ($mapped['notes'] ?? null) ?: null;
        if ($notes && MyNumberDetector::containsMyNumberLikeString($notes)) {
            $warnings[] = 'メモ欄にマイナンバーらしき文字列が含まれています。';
        }

        $title = trim((string) ($mapped['title'] ?? ''));

        $resolved = [
            'client_id' => $clientId,
            'procedure_type_id' => $procedureTypeId,
            'title' => $title,
            'due_date' => $dueDate,
            'original_due_date' => $dueDate,
            'status' => $status,
            'assigned_user_id' => $assignedUserId,
            'notes' => $notes,
        ];

        $validator = Validator::make(['title' => $title, 'notes' => $notes], [
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        foreach ($validator->errors()->all() as $message) {
            $errors[] = $message;
        }

        $display = [
            ['label' => '顧問先名', 'value' => $clientName],
            ['label' => '手続き種別名', 'value' => $procedureTypeName],
            ['label' => 'タイトル', 'value' => $title],
            ['label' => '期限日', 'value' => (string) ($dueDate ?? $dueDateInput)],
            ['label' => 'ステータス', 'value' => $status->label()],
            ['label' => '担当者', 'value' => $assignedUserName],
            ['label' => 'メモ', 'value' => (string) ($notes ?? '')],
        ];

        $customFields = $this->resolveCustomFields(
            $this->customFieldDefinitions($office, CustomFieldTarget::ProcedureTask),
            $mapped,
        );
        $errors = [...$errors, ...$customFields['errors']];
        $display = [...$display, ...$customFields['display']];
        $resolved['custom_fields'] = $customFields['values'];

        return ['errors' => $errors, 'warnings' => $warnings, 'resolved' => $resolved, 'display' => $display];
    }

    public function create(array $resolved): ProcedureTask
    {
        return ProcedureTask::create($resolved);
    }
}
