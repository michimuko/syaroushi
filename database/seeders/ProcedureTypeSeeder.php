<?php

namespace Database\Seeders;

use App\Models\ProcedureType;
use Illuminate\Database\Seeder;

/**
 * 主要な法定手続きのグローバルマスタ（企画書10章 Phase2、office_idを持たない）。
 * recurrence_ruleの実際の展開ロジック（procedures:generate-upcoming）はPhase4で実装する。
 * ここではマスタデータの投入のみを行う。
 */
class ProcedureTypeSeeder extends Seeder
{
    public function run(): void
    {
        $procedureTypes = [
            [
                'name' => '算定基礎届（定時決定）',
                'category' => '社会保険',
                'recurrence_type' => 'yearly',
                'recurrence_rule' => ['month' => 7, 'day' => 10],
                'default_lead_days' => [90, 30, 7],
                'description' => '毎年7月、4〜6月の報酬月額を基に標準報酬月額を見直す届出。',
            ],
            [
                'name' => '労働保険 年度更新（概算・確定保険料申告）',
                'category' => '労働保険',
                'recurrence_type' => 'yearly',
                'recurrence_rule' => ['month' => 7, 'day' => 10],
                'default_lead_days' => [90, 30, 7],
                'description' => '前年度の確定保険料と当年度の概算保険料を申告・納付する手続き。',
            ],
            [
                'name' => '36協定届（時間外・休日労働に関する協定届）',
                'category' => '労務管理',
                'recurrence_type' => 'yearly',
                'recurrence_rule' => ['note' => '有効期間は事業所ごとに異なるため満了日を個別管理'],
                'default_lead_days' => [60, 30, 14],
                'description' => '時間外労働・休日労働をさせる場合に必要な労使協定の届出。有効期間満了前に更新が必要。',
            ],
            [
                'name' => '就業規則（変更）届',
                'category' => '就業規則',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [30, 14, 7],
                'description' => '就業規則を新規作成・変更した際に所轄労働基準監督署へ届け出る。',
            ],
            [
                'name' => '賞与支払届',
                'category' => '社会保険',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [14, 7, 3],
                'description' => '賞与を支給した都度、支給日から5日以内に提出する届出。',
            ],
            [
                'name' => '月額変更届（随時改定）',
                'category' => '社会保険',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [30, 14, 7],
                'description' => '固定的賃金の変動により標準報酬月額に2等級以上の差が生じた場合の届出。',
            ],
            [
                'name' => '労働者名簿の整備',
                'category' => '労務管理',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [14, 7, 3],
                'description' => '従業員の氏名・住所等を記載した名簿を整備・更新する（労働基準法上の義務）。',
            ],
            [
                'name' => '雇用保険 被保険者資格取得届',
                'category' => '労働保険',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [10, 5, 1],
                'description' => '従業員を新たに雇用した際、雇用した月の翌月10日までに提出。',
            ],
            [
                'name' => '雇用保険 被保険者資格喪失届',
                'category' => '労働保険',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [10, 5, 1],
                'description' => '従業員が退職した際、退職日の翌日から10日以内に提出。',
            ],
            [
                'name' => '健康保険・厚生年金保険 被保険者資格取得届',
                'category' => '社会保険',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [5, 3, 1],
                'description' => '従業員を新たに雇用した際、資格取得日から5日以内に提出。',
            ],
            [
                'name' => '健康保険・厚生年金保険 被保険者資格喪失届',
                'category' => '社会保険',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [5, 3, 1],
                'description' => '従業員が退職した際、資格喪失日から5日以内に提出。',
            ],
            [
                'name' => '産前産後休業取得者申出書',
                'category' => '育児・介護休業',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [14, 7, 3],
                'description' => '産前産後休業を取得する従業員について、保険料免除のために提出する申出書。',
            ],
            [
                'name' => '育児休業等取得者申出書',
                'category' => '育児・介護休業',
                'recurrence_type' => 'one_time',
                'recurrence_rule' => null,
                'default_lead_days' => [14, 7, 3],
                'description' => '育児休業を取得する従業員について、保険料免除のために提出する申出書。',
            ],
            [
                'name' => '高年齢雇用継続給付 支給申請',
                'category' => '雇用保険給付',
                'recurrence_type' => 'monthly',
                'recurrence_rule' => ['interval_months' => 2],
                'default_lead_days' => [14, 7, 3],
                'description' => '60歳以降に賃金が低下した従業員に対する給付金の支給申請（原則2か月ごと）。',
            ],
            [
                'name' => '労働保険 概算保険料の分割納付（延納）第2期',
                'category' => '労働保険',
                'recurrence_type' => 'yearly',
                'recurrence_rule' => ['month' => 10, 'day' => 31],
                'default_lead_days' => [30, 14, 7],
                'description' => '年度更新時に概算保険料を延納（分割納付）する場合の第2期納付期限。',
            ],
        ];

        foreach ($procedureTypes as $procedureType) {
            ProcedureType::query()->updateOrCreate(
                ['name' => $procedureType['name']],
                [...$procedureType, 'is_active' => true],
            );
        }
    }
}
