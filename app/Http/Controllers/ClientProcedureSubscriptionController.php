<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientProcedureSubscription;
use App\Models\ProcedureType;
use Illuminate\Http\Request;

class ClientProcedureSubscriptionController extends Controller
{
    /**
     * 顧問先の対象手続き（購読設定）をまとめて更新する。
     * client_procedure_subscriptionsの編集権限は顧問先編集権限に準じる（企画書7.5章）。
     * 行を削除せずis_activeで管理する（lead_days_override等の設定を将来失わないため）。
     * lead_days_overridesは procedure_type_id => 日数配列 のマップ。空配列/未指定はnullとして
     * 保存し、リマインドバッチ側は procedure_type.default_lead_days にフォールバックする
     * （SendReminderNotificationsCommand::leadDaysFor()参照）。
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $request->validate([
            'procedure_type_ids' => 'array',
            'procedure_type_ids.*' => 'integer|exists:procedure_types,id',
            'lead_days_overrides' => 'array',
            'lead_days_overrides.*' => 'nullable|array',
            'lead_days_overrides.*.*' => 'integer|min:0',
        ]);

        $activeIds = array_map('intval', $validated['procedure_type_ids'] ?? []);
        $overrides = $validated['lead_days_overrides'] ?? [];

        foreach (ProcedureType::query()->pluck('id') as $procedureTypeId) {
            $override = $overrides[$procedureTypeId] ?? null;

            ClientProcedureSubscription::query()->updateOrCreate(
                [
                    'client_id' => $client->id,
                    'procedure_type_id' => $procedureTypeId,
                ],
                [
                    'is_active' => in_array($procedureTypeId, $activeIds, true),
                    'lead_days_override' => ! empty($override) ? array_map(intval(...), $override) : null,
                ],
            );
        }

        return redirect()->route('clients.edit', $client)->with('success', '対象手続きを更新しました。');
    }
}
