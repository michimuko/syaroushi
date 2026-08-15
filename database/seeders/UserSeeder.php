<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * DatabaseSeederで作成済みの開発用事務所にスタッフを追加し、さらに
 * テナント分離（企画書7.6章）の動作確認用に、完全に別テナントの事務所
 * （ownerユーザー・スタッフ付き）をもう1件用意する。
 * 「他事務所のデータが一切見えないこと」をログインを切り替えて確認できるようにする。
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $mainOffice = Office::query()->where('name', '開発用社労士事務所')->firstOrFail();

        User::factory()->for($mainOffice)->create(['name' => '佐藤 花子']);
        User::factory()->for($mainOffice)->create(['name' => '鈴木 一郎']);

        $otherOffice = Office::factory()->create([
            'name' => 'テスト社会保険労務士法人',
        ]);

        User::factory()->for($otherOffice)->owner()->create([
            'name' => '高橋 事務所長',
            'email' => 'owner2@example.com',
        ]);

        User::factory()->for($otherOffice)->create(['name' => '田中 スタッフ']);
    }
}
