<?php

namespace Database\Seeders;

use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * マルチテナント方針（企画書7.6章）に基づき、開発動作確認用に
     * 最低1件のofficesレコードと、その所属ownerユーザーを用意する。
     */
    public function run(): void
    {
        $office = Office::factory()->create([
            'name' => '開発用社労士事務所',
        ]);

        User::factory()->for($office)->owner()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
