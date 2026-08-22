<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * V1リリースに向けた料金の再設計（2026-08-22）。
 *
 * 初期の階層プラン制（5段階、企画書11章）は「オフィスステーションより一貫して安く」という
 * 相対基準のみで決めた仮設定で、ライト→スタンダードの値上げ幅がほぼ無い歪みがあった。
 * 個人開発・実績ゼロでのリリースであること、事務所側の稟議承認ライン（担当者単独で
 * 承認できる目安は月額1万円以内）、期限管理という単機能ツールであること（オフィス
 * ステーションのような手続き作成・電子申請までは行わない）を踏まえ、以下の方針で
 * 4段階（実質3段階＋個別見積り）に再設計した。
 *
 * - 「ライト」は廃止し、スターター/スタンダード/プロフェッショナルの3段階＋
 *   エンタープライズ（個別見積り）に整理
 * - スターター・スタンダードは1万円以内に収め、事務所の担当者が単独で稟議を通せる
 *   価格帯にする（導入のハードルを下げることを最優先）
 * - プロフェッショナルは対象事務所（既に複数SaaSに予算を持つ規模）向けのため1万円超も許容
 * - 値上げ幅を+143%→+118%と滑らかにし、末尾を「X,800円」に統一
 *
 * あわせてStripeサンドボックス（korotaneDXengineerサンドボックス）側のProduct/Priceも
 * 新価格で作り直し済み（「ライト」Productはactive=falseにアーカイブ、他3プランは新しい
 * Priceを作成してdefault_priceを差し替え、旧Priceはactive=falseに）。このマイグレーションで
 * 発行済みのPrice IDをbilling_plans.stripe_price_idに反映する。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('billing_plans')->where('name', 'ライト')->delete();

        DB::table('billing_plans')->where('name', 'スターター')->update([
            'max_clients' => 15,
            'max_users' => 3,
            'monthly_price' => 2800,
            'sort_order' => 1,
            'stripe_price_id' => 'price_1U7ChjLDZt9DstaRMEfn2aEo',
            'updated_at' => now(),
        ]);

        DB::table('billing_plans')->where('name', 'スタンダード')->update([
            'max_clients' => 50,
            'max_users' => 8,
            'monthly_price' => 6800,
            'sort_order' => 2,
            'stripe_price_id' => 'price_1U7ChlLDZt9DstaR9ok8NVNk',
            'updated_at' => now(),
        ]);

        DB::table('billing_plans')->where('name', 'プロフェッショナル')->update([
            'max_clients' => 150,
            'max_users' => 20,
            'monthly_price' => 14800,
            'sort_order' => 3,
            'stripe_price_id' => 'price_1U7ChnLDZt9DstaRGcaPAyO6',
            'updated_at' => now(),
        ]);

        DB::table('billing_plans')->where('name', 'エンタープライズ')->update([
            'sort_order' => 4,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('billing_plans')->where('name', 'スターター')->update([
            'max_clients' => 20,
            'max_users' => 3,
            'monthly_price' => 8000,
            'sort_order' => 1,
            'stripe_price_id' => null,
            'updated_at' => now(),
        ]);

        DB::table('billing_plans')->insert([
            'name' => 'ライト',
            'max_clients' => 50,
            'max_users' => 6,
            'monthly_price' => 13800,
            'sort_order' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('billing_plans')->where('name', 'スタンダード')->update([
            'max_clients' => 100,
            'max_users' => 10,
            'monthly_price' => 14800,
            'sort_order' => 3,
            'stripe_price_id' => null,
            'updated_at' => now(),
        ]);

        DB::table('billing_plans')->where('name', 'プロフェッショナル')->update([
            'max_clients' => 200,
            'max_users' => 20,
            'monthly_price' => 24800,
            'sort_order' => 4,
            'stripe_price_id' => null,
            'updated_at' => now(),
        ]);

        DB::table('billing_plans')->where('name', 'エンタープライズ')->update([
            'sort_order' => 5,
            'updated_at' => now(),
        ]);
    }
};
