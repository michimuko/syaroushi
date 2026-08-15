<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientProcedureSubscription;
use App\Models\ProcedureType;
use Illuminate\Database\Seeder;

/**
 * 顧問先ごとに手続きマスタを複数購読させる。購読の有無・停止中の購読・
 * lead_days上書きありなしのパターンを一通り作り、購読設定画面
 * （ClientProcedureSubscriptionController）の表示を確認できるようにする。
 */
class ClientProcedureSubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        Client::query()->each(function (Client $client) {
            $procedureTypes = ProcedureType::query()->inRandomOrder()->take(4)->get();

            $procedureTypes->each(function (ProcedureType $procedureType, int $index) use ($client) {
                ClientProcedureSubscription::query()->create([
                    'office_id' => $client->office_id,
                    'client_id' => $client->id,
                    'procedure_type_id' => $procedureType->id,
                    'is_active' => $index !== 0,
                    'lead_days_override' => $index === 1 ? [45, 20, 5] : null,
                ]);
            });
        });
    }
}
