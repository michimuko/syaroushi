<?php

namespace Database\Seeders;

use App\Models\PlatformAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * SaaS運営者アカウントを.envの値から1件作成する。
 * メールアドレス・パスワードをコードにハードコードせず、環境ごとに変えられるようにする。
 */
class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('PLATFORM_ADMIN_EMAIL');
        $password = env('PLATFORM_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command->warn(
                'PLATFORM_ADMIN_EMAIL / PLATFORM_ADMIN_PASSWORD が.envに設定されていないため、'
                .'運営者アカウントの作成をスキップしました。'
            );

            return;
        }

        PlatformAdmin::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => '運営者',
                'password' => Hash::make($password),
            ],
        );
    }
}
