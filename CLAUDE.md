# CLAUDE.md

このファイルはClaude Codeがこのリポジトリで作業する際の索引。詳細は各リンク先を参照すること。

## プロジェクト概要

社労士事務所向けの「業務進捗・期限管理SaaS」。顧問先ごとの法定手続き（算定基礎届、労働保険年度更新、36協定届等）の進捗と期限を一元管理し、自動アラートで期限漏れを防ぐ。

詳細な企画背景・市場調査・DB設計・ロードマップは **[docs/sharoushi-tool-project-brief.md](docs/sharoushi-tool-project-brief.md)** を参照（フルコンテキスト）。

現在のステータス：**要件定義中**。実装は未着手（Laravel初期スケルトンのみ、Breeze・Sail構成もこれから）。

企画書11章に記載の未確定事項（価格モデル、マルチテナント方針、計算アシスタント機能Dの詳細仕様など）は要件定義で詰めてから実装に着手する。

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
