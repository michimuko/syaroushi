<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Office;
use Illuminate\Database\Seeder;

/**
 * 各事務所に顧問先を複数作成する。担当者ありなし・稼働中/契約終了・
 * カスタムフィールドあり等、一覧・詳細・編集画面の表示パターンを一通り確認できるようにする。
 */
class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Office::query()->each(function (Office $office) {
            $users = $office->users;

            Client::factory()
                ->for($office)
                ->count(6)
                ->sequence(
                    ['assigned_user_id' => $users->first()?->id],
                    ['assigned_user_id' => $users->last()?->id],
                    ['assigned_user_id' => null],
                )
                ->create();

            Client::factory()->for($office)->inactive()->create([
                'name' => '解約済み株式会社',
                'assigned_user_id' => null,
                'notes' => '契約終了。データは参照可能な状態で保持。',
            ]);

            Client::factory()->for($office)->create([
                'name' => 'カスタム項目テスト株式会社',
                'assigned_user_id' => $users->first()?->id,
                'custom_fields' => [
                    '担当社労士' => $users->first()?->name,
                    '顧問料' => '30,000円/月',
                ],
            ]);
        });
    }
}
