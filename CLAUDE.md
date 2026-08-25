# CLAUDE.md

このファイルはClaude Codeがこのリポジトリで作業する際の索引。詳細は各リンク先を参照すること。

## プロジェクト概要

社労士事務所向けの「業務進捗・期限管理SaaS」。顧問先ごとの法定手続き（算定基礎届、労働保険年度更新、36協定届等）の進捗と期限を一元管理し、自動アラートで期限漏れを防ぐ。

詳細な企画背景・市場調査・DB設計・ロードマップは **[docs/sharoushi-tool-project-brief.md](docs/sharoushi-tool-project-brief.md)** を参照（フルコンテキスト）。

現在のステータス：**Phase 6完了**（2026-08-15）。DB設計・権限モデル・マルチテナント方針・個人情報保護要件は確定済み（企画書7章・11章）。Phase 1〜4（認証・顧問先/タスクCRUD・カレンダー・自動生成バッチ・メール/Web Push通知）、Phase 5（S3(MinIO)切り替え・`procedure_task_documents`書類チェックリスト・署名付きURL・保存期限管理・`document_access_logs`・自由入力欄へのマイナンバー検知警告・Excel移行アシスタント）に加え、Phase 6の**顧問先向け進捗レポートPDF自動生成**（差別化B、`ClientReport`／`app/Services/ClientReportPdfGenerator.php`、日本語フォント埋め込み対応）と**計算アシスタント**（差別化D、`app/Services/CalcAssistant/`：年次有給休暇の付与日数計算・時間外労働時間/36協定上限チェック・勤務シフト表作成支援の3機能、タスク詳細から呼び出し`procedure_tasks.calc_result`に保存可能）まで実装済み。

企画書11章に記載の残る未確定事項（価格モデルの具体数値など）はPhase7着手時に詰める。Phase7のうち**カスタムフィールドの本格活用**（`CustomFieldDefinition`：テキスト・数値・日付・選択肢・チェックボックスの5種類、owner限定で管理番号・契約プラン等の独自項目を追加でき顧問先/手続きタスクの登録・編集画面に自動反映、値は`clients.custom_fields`／`procedure_tasks.custom_fields`にJSON保存、Excelエクスポート/インポートにも反映済み）と**ダッシュボード集計強化**（`DashboardController`：担当者別ワークロード（期限超過件数の内訳付き）・手続き種別ごとの内訳を追加、タスク一覧の`procedure_type_id`絞り込みと連動）は実装済み。**権限管理の高度化**（`App\Enums\Permission`：owner限定操作のうち手続き種別マスタ編集・カスタムフィールド設定・Excel移行ウィザード実行の3つを、ownerが`users.permissions`（JSON配列）でstaffへ個別委譲できる。`User::hasPermission()`をPolicy／Gateから参照し、ownerは常に全権限。顧問先削除・ユーザー管理は委譲対象外のままowner限定を維持）は実装済み。**事務所ごとの契約プラン・課金機能**（企画書11章）も実装済み。当初は顧問先数に応じた従量制だったが、**2026-08-18に階層プラン制へ移行**（`billing_plans`マスタ：`max_clients`・`max_users`・`monthly_price`を保持）。当初はスターター／ライト／スタンダード／プロフェッショナル／エンタープライズの5段階だったが、「オフィスステーションより安く」という相対基準のみで決めた仮設定で、ライト→スタンダードの値上げ幅がほぼ無い歪みがあった。個人開発・実績ゼロでのV1リリースであること、事務所の稟議承認ライン（担当者単独で通せる目安は月額1万円以内）、期限管理という単機能ツールである点（電子申請等は範囲外）を踏まえ、**2026-08-22に価格を再設計**（ライトは廃止し4段階に整理：スターター 2,800円/15顧問先・3ユーザー、スタンダード 6,800円/50顧問先・8ユーザー、プロフェッショナル 14,800円/150顧問先・20ユーザー、エンタープライズ＝個別見積り。スターター・スタンダードは1万円以内に収め、プロフェッショナルは対象事務所の予算規模を踏まえ1万円超も許容する方針）。LPにもこの料金表を掲載する（当初の「料金は問い合わせ導線のみ」から変更）。`offices.billing_plan_id`で事務所ごとにプランを割り当て、`offices.custom_monthly_price`（旧`unit_price_override`から改名、自由入力の契約プラン欄は廃止）で交渉時の個別値引き月額を上書き可能。`BillingSetting`は単価を持たなくなり、トライアル日数・請求サイクルのみの全事務所共通シングルトン設定に縮小（運営者が`/admin/billing-settings`から変更可能）。`offices.trial_ends_at`を契約時に自動設定し、`Office::isTrialActive()`でトライアル判定。`billing:generate-invoices`バッチ（毎月1日実行）が前月分の対象事務所の月額プラン料金で`OfficeInvoice`を生成、トライアル中・利用停止中の事務所はスキップ。事務所オーナーは`/settings/billing`で契約状況・見込み金額・請求履歴を閲覧可能（owner限定、staffへの委譲対象外）。

**Stripe決済連携（2026-08-18〜着手）**：従来「決済代行連携は範囲外、請求額の管理機能に留める」としていたスコープを変更し、実際の決済処理をStripeで行う方針に転換。運営元は複数のtoB DX SaaS（社労士事務所向け＝本プロダクト、ハチの巣駆除業者向け、シロアリ駆除業者向け等）を同一ブランド「korotane」で展開する構想があり、Stripeアカウントは全プロダクト共通の1アカウント（`korotaneDXengineer`）に統一する方針。Stripeサンドボックス（`korotaneDXengineerサンドボックス`）には当初旧5プラン（2026-08-18時点の価格）分のProduct/Priceを作成していたが、2026-08-22の価格再設計にあわせて作り直し済み。「ライト」Productは廃止のためactive=falseにアーカイブ、スターター／スタンダード／プロフェッショナルの3Productは新しいPrice（スターター2,800円=`price_1U7ChjLDZt9DstaRMEfn2aEo`、スタンダード6,800円=`price_1U7ChlLDZt9DstaR9ok8NVNk`、プロフェッショナル14,800円=`price_1U7ChnLDZt9DstaRGcaPAyO6`）を作成してdefault_priceを差し替え、旧Priceはactive=falseに。これらのPrice IDは`billing_plans.stripe_price_id`にも反映済み（`database/migrations/2026_08_22_180000_update_billing_plans_v1_pricing.php`）。エンタープライズのみ個別見積りのためPriceなし・変更なし。アプリ側の実装は着手済み（2026-08-24時点）：事務所オーナー自身が`/settings/billing`から行う`SubscriptionController`（Checkout Sessions `mode=subscription`での契約開始、Customer Portalでのセルフサービス管理）と、`StripeWebhookController`（`checkout.session.completed`／`customer.subscription.created・updated・deleted`／`invoice.payment_failed`／`invoice.paid`を処理し`offices.stripe_subscription_id`/`stripe_subscription_status`/`stripe_payment_failed_at`を追従）は実装・テスト済み。月次請求バッチ`billing:generate-invoices`（毎月1日実行）はStripeで決済連携が開始済み（`Office::isStripeManaged()`：`stripe_subscription_id`があり`stripe_subscription_status`が`canceled`でない）の事務所を意図的にスキップし、未契約事務所向けのDB請求記録生成のみを行う設計（Stripe側の自動課金とは別系統・意図的に非統合）。この判定は支払い失敗による`past_due`／`unpaid`等の一時的な異常状態でもスキップを継続する（Stripe側が請求の正であることに変わりはなく、DB側で重複請求記録を作らないため。以前は`hasActiveStripeSubscription()`＝`active`/`trialing`限定でスキップ判定していたためpast_due中に二重生成される欠陥があったが2026-08-24に修正）。`invoice.payment_failed`受信時は`offices.stripe_payment_failed_at`に打刻し、`PlatformAdmin`全員へ`StripePaymentFailed`通知（メール）を送る。`invoice.paid`受信でこのフラグは自動的にクリアされる（決済が失敗中でもアプリの利用自体はブロックしない設計のまま、運営者が能動的に気づけるようにする目的の表示専用カラム）。運営管理画面（`Platform/Offices/Index`・`Edit`）には「支払いエラー」「プラン未設定」「トライアル終了間近（7日以内）」の3種のバッジ・警告バナーと、一覧の「要対応のみ表示」フィルタ（`Office::scopeNeedsBillingAttention()`、`Office::billing_attention_reasons`アクセサと同じ判定基準）を実装済み（`office_invoices`テーブルにStripe請求書と紐付けるカラムはまだ無いが、上記により支払い失敗の可視化と二重請求記録の防止は解消済み）。運営管理画面には2026-08-24追加で、Stripe定期契約済みの事務所の編集画面に**「請求確定」ボタン**（`Platform/OfficeController@syncBilling`）があり、押すと現在保存されている設定金額（`custom_monthly_price`優先、無ければプランの`monthly_price`）をStripeサブスクリプション価格に同期し、差額をその場でプロレーション即時請求する（`StripeSubscriptionGateway::syncSubscriptionPrice()`）。また運営管理画面のトライアル期間設定（`trial_days`）を変更すると、まだStripe契約が始まっていないトライアル中の既存事務所についても契約日基準で`trial_ends_at`を再計算するようになっている（Stripe契約済み・トライアル終了済みの事務所は対象外）。**トライアル→本課金移行のStripe標準trial化（2026-08-25）**：セルフサインアップ（`/register`）がトライアル期限・プランを設定しないまま宙ぶらりんになる欠陥と、解約後にDB請求バッチが請求記録の生成を再開してしまう欠陥を解消するため、トライアル開始時にStripe Checkoutでカードを先に預かる方式へ移行（LPの「クレジットカード登録不要」表記は削除）。登録画面でプラン（スターター／スタンダード／プロフェッショナルのみ、エンタープライズは対象外）を選択すると`trial_ends_at`・`billing_plan_id`が即座に設定され、登録直後にStripe Checkoutへ自動遷移して`subscription_data.trial_end`にトライアル終了日を渡す（`App\Services\Stripe\StripeCheckoutStarter`）。トライアル終了の瞬間にStripe側が自動で本課金を開始するため、運営者が管理画面で見るトライアル期日と実際の課金開始タイミングは常に一致する。`Office::isStripeManaged()`は`hasEverHadStripeSubscription()`に置き換え、`stripe_subscription_id`が一度でも設定された事務所は解約後も永久にDB請求バッチの対象外とした（解約＝サービス終了として扱う）。解約webhook（`customer.subscription.deleted`）受信時は`offices.is_active`も自動でfalseにする。あわせて`office_invoices.paid_at`を新設し、Stripe非連携（一度もCheckoutしていない）事務所向けのDB請求記録について運営者が入金確認できる**未収金管理画面**（`/admin/receivables`）を追加（Stripe契約者の決済状況はStripe側が正のため対象外）。本番ドメインは`korotane.site`直下で運用中だが、複数プロダクト展開に備えてサブドメイン構成（例: `sharoushi.korotane.site`）への移行も合わせて検討中。**ネイティブデスクトップ通知アプリ**（差別化C・Level2、Tauri）は、Laravel側のAPI基盤（Sanctumによる個人アクセストークン発行・失効画面`/settings/desktop-app`、ポーリングAPI`GET /api/desktop/notifications`。既存のメール/Web Push向け`NotificationLog`を再利用し、`channel='desktop'`の行でAPI配信済みを記録して同じリマインドを重複配信しない設計）に加え、Tauri本体（`desktop-app/`、Rust製の常駐トレイアプリ）まで実装済み。トレイ常駐・設定画面（URL/トークン/確認間隔/自動起動）・バックグラウンドポーリング・OS通知表示を実装し、`cargo build --release`・`cargo clippy`・フロントエンドの`npm run build`は通ることを確認済み。ただしこの開発環境には実ディスプレイ（X/Wayland）が無いため、トレイアイコン表示やOS通知表示自体の目視確認はできておらず、GUI環境での実機確認は未実施（詳細は`desktop-app/README.md`）。**事務所ごとのオプションモジュールON/OFF**（2026-08-17実装）：`App\Enums\Module`（Excel移行支援／PDFレポート／Web Push通知／計算アシスタント／カスタムフィールドの5種類）を事務所単位で個別に有効化・無効化できる。`offices.enabled_modules`（JSON配列、未設定＝全モジュール有効で既存事務所と後方互換）を運営者が`/admin/offices/{office}/edit`のチェックボックスで設定し、`EnsureModuleEnabled`ミドルウェア（ルートに`module:calc_assistant`等で指定）とVue側のナビ表示（Inertia共有プロップ`auth.enabledModules`）の両方でゲートする。モジュール間の依存関係はなく独立してON/OFF可能（プリセットボタンはUI上のショートカットで強制ではない）。

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
