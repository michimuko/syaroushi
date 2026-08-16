# CLAUDE.md

このファイルはClaude Codeがこのリポジトリで作業する際の索引。詳細は各リンク先を参照すること。

## プロジェクト概要

社労士事務所向けの「業務進捗・期限管理SaaS」。顧問先ごとの法定手続き（算定基礎届、労働保険年度更新、36協定届等）の進捗と期限を一元管理し、自動アラートで期限漏れを防ぐ。

詳細な企画背景・市場調査・DB設計・ロードマップは **[docs/sharoushi-tool-project-brief.md](docs/sharoushi-tool-project-brief.md)** を参照（フルコンテキスト）。

現在のステータス：**Phase 6完了**（2026-08-15）。DB設計・権限モデル・マルチテナント方針・個人情報保護要件は確定済み（企画書7章・11章）。Phase 1〜4（認証・顧問先/タスクCRUD・カレンダー・自動生成バッチ・メール/Web Push通知）、Phase 5（S3(MinIO)切り替え・`procedure_task_documents`書類チェックリスト・署名付きURL・保存期限管理・`document_access_logs`・自由入力欄へのマイナンバー検知警告・Excel移行アシスタント）に加え、Phase 6の**顧問先向け進捗レポートPDF自動生成**（差別化B、`ClientReport`／`app/Services/ClientReportPdfGenerator.php`、日本語フォント埋め込み対応）と**計算アシスタント**（差別化D、`app/Services/CalcAssistant/`：年次有給休暇の付与日数計算・時間外労働時間/36協定上限チェック・勤務シフト表作成支援の3機能、タスク詳細から呼び出し`procedure_tasks.calc_result`に保存可能）まで実装済み。

企画書11章に記載の残る未確定事項（価格モデルの具体数値など）はPhase7着手時に詰める。Phase7のうち**カスタムフィールドの本格活用**（`CustomFieldDefinition`：テキスト・数値・日付・選択肢・チェックボックスの5種類、owner限定で管理番号・契約プラン等の独自項目を追加でき顧問先/手続きタスクの登録・編集画面に自動反映、値は`clients.custom_fields`／`procedure_tasks.custom_fields`にJSON保存、Excelエクスポート/インポートにも反映済み）と**ダッシュボード集計強化**（`DashboardController`：担当者別ワークロード（期限超過件数の内訳付き）・手続き種別ごとの内訳を追加、タスク一覧の`procedure_type_id`絞り込みと連動）は実装済み。**権限管理の高度化**（`App\Enums\Permission`：owner限定操作のうち手続き種別マスタ編集・カスタムフィールド設定・Excel移行ウィザード実行の3つを、ownerが`users.permissions`（JSON配列）でstaffへ個別委譲できる。`User::hasPermission()`をPolicy／Gateから参照し、ownerは常に全権限。顧問先削除・ユーザー管理は委譲対象外のままowner限定を維持）は実装済み。**事務所ごとの契約プラン・課金機能**（企画書11章：課金軸は顧問先数に応じた従量制。`BillingSetting`：単価・トライアル日数・請求サイクルを持つ全事務所共通のシングルトン設定で、運営者（platformガード）が`/admin/billing-settings`から変更可能。`offices.trial_ends_at`を契約時に自動設定し、`Office::isTrialActive()`でトライアル判定。`billing:generate-invoices`バッチ（毎月1日実行）が前月分の課金対象顧問先数×単価で`OfficeInvoice`を生成、トライアル中・利用停止中の事務所はスキップ。事務所オーナーは`/settings/billing`で契約状況・見込み金額・請求履歴を閲覧可能（owner限定、staffへの委譲対象外）。決済代行連携（Stripe等）は範囲外で、あくまで請求額の管理機能に留める）も実装済み。**ネイティブデスクトップ通知アプリ**（差別化C・Level2、Tauri）は、Laravel側のAPI基盤（Sanctumによる個人アクセストークン発行・失効画面`/settings/desktop-app`、ポーリングAPI`GET /api/desktop/notifications`。既存のメール/Web Push向け`NotificationLog`を再利用し、`channel='desktop'`の行でAPI配信済みを記録して同じリマインドを重複配信しない設計）に加え、Tauri本体（`desktop-app/`、Rust製の常駐トレイアプリ）まで実装済み。トレイ常駐・設定画面（URL/トークン/確認間隔/自動起動）・バックグラウンドポーリング・OS通知表示を実装し、`cargo build --release`・`cargo clippy`・フロントエンドの`npm run build`は通ることを確認済み。ただしこの開発環境には実ディスプレイ（X/Wayland）が無いため、トレイアイコン表示やOS通知表示自体の目視確認はできておらず、GUI環境での実機確認は未実施（詳細は`desktop-app/README.md`）。

## 技術スタック

| レイヤー | 技術 |
|---|---|
| バックエンド | Laravel 13 |
| フロントエンド | Vue 3（Composition API） |
| 連携層 | Inertia.js |
| UI | Tailwind CSS（Laravel Breezeベース） |
| カレンダーUI | `@fullcalendar/vue3` |
| PDF生成 | `laravel-dompdf` |
| Push通知 | `laravel-notification-channels/webpush` |
| テスト | Pest |
| 開発環境 | Laravel Sail（Docker） |

詳細な選定理由は企画書6章を参照。

## 開発フロー

- 環境操作（起動・停止・artisan・composer・npm・テスト実行）は**必ずSail経由**で行う → `.claude/skills/laravel-sail-commands`
- 新しい画面・フォーム・APIを実装するとき、実装完了を報告する前に確認するチェックリスト（エラー処理・Enterキー挙動・初期値・バリデーション・権限・N+1対策など）→ `.claude/skills/implementation-checklist`
- 画面・コンポーネントをデザインするとき（テーブル・フォーム・モーダル・カレンダー・ダッシュボード）→ `.claude/skills/ui-design-guidelines`
- グラフ・チャートを描く場合は上記ではなく `dataviz` スキルを使う
- 期限計算・周期ルールなどの重要ロジックは必ずPestで単体テストを書く

### コミット方針（2026-08-15確定）

- **機能・画面単位でコミットを分ける**。複数の機能をまとめた大きなコミットは避ける（レビュー・切り戻しのしやすさを優先）
- 1つの画面・機能ができたら、コミット前に必ず次の順序を踏む：
  1. Sail経由でPestテストを書いて実行し、パスすることを確認する（期限計算・権限・テナント分離など重要ロジックは特に）
  2. 実際に画面・APIを動かして（ブラウザでの操作、もしくは`sail artisan tinker`／`route:list`等での確認）意図通り動くことを確認する
  3. 上記2点が確認できてから`git commit`し、pushする
- テストが書けない・動作確認ができない段階（DBスキーマだけ等）でコミットする場合も、次のコミットで必ずテスト・動作確認をセットで行う
- 動作確認やテストをスキップしたまま「実装完了」として報告しない

## 設計上の重要原則（企画書5章より）

**コア機能**（顧問先CRUD、手続きタスク管理、カレンダー、メール通知）と**オプションモジュール**（Excel移行支援／PDFレポート／Web Push通知／計算アシスタント／カスタムフィールド）を明確に分離する。初期状態ではオプションモジュールは非表示にし、設定画面から個別にON/OFFできる構成にする。機能を詰め込みすぎて使いにくくしない。

**拡張性・保守性を常に意識する（2026-08-15確定の恒久方針）**：本プロダクトは将来的に複数の社労士事務所へのSaaS展開を前提とする（企画書7.6章）。単一事務所・単一顧客だけを想定した実装（決め打ちの値、テナント分離を考慮しないクエリ、事務所間の分離が構造的に保証されないコード）は避け、設計・実装の判断に迷ったら「後から安全に拡張できるか」「他の事務所が増えても保守負荷が線形以上に増えないか」を判断基準にする。特にDBスキーマ変更・権限まわり・マルチテナント関連のコードは、この原則を最優先で適用すること。

## 実装ロードマップ（要約）

1. Phase 1：Breeze（Inertia+Vue3）セットアップ、認証、`users`/`clients` CRUD
2. Phase 2：`procedure_types`マスタ、`client_procedure_subscriptions`、`procedure_tasks`のCRUD
3. Phase 3：FullCalendarでのカレンダー表示
4. Phase 4：自動生成バッチ＋通知（メール＋Web Push）
5. Phase 5〜7：差別化モジュール（Excel移行、PDFレポート、計算アシスタント、カスタムフィールド、マルチテナント化等）

詳細は企画書10章を参照。
