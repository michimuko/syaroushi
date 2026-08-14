---
name: laravel-sail-commands
description: Laravel Sail（Docker）でのこのプロジェクトの起動・停止・artisan/composer/npm実行・テスト・DB操作コマンド集。「サーバー起動して」「マイグレーションして」「テスト回して」「sailって何？」など、環境操作やコマンド実行が必要な場面で使う。
---

# Laravel Sail コマンドリファレンス

このプロジェクトは **Laravel Sail**（Docker上の開発環境）を前提とする。`php`や`composer`をホストに直接インストールしなくても、Docker経由で動く。

## 現状の注意（重要）

- `vendor/bin/sail` は導入済みだが、**`docker-compose.yml` はまだ生成されていない**（`sail:install` 未実行）。
- `.env.example` の `DB_CONNECTION` は `sqlite` になっている。Sailで MySQL/Redis 等のコンテナ構成にするなら `sail:install` 時に選び直し、`.env` の `DB_*` も書き換える必要がある。
- 初回セットアップの手順は本ファイル末尾の「初回セットアップ」を参照。

## 基本方針

- **すべてのPHP/Artisan/Composer/npmコマンドはSail経由で実行する**（ホストにPHPが入っていても直接叩かない。PHPバージョンや拡張の差異でバグる原因になる）
- 長く打つのが面倒な場合はシェルエイリアスを使ってよい：
  ```bash
  alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
  ```

## 環境の起動・停止

```bash
./vendor/bin/sail up -d       # バックグラウンドで起動
./vendor/bin/sail up          # フォアグラウンド起動（ログを見たい時）
./vendor/bin/sail down        # 停止・コンテナ削除
./vendor/bin/sail stop        # 停止のみ（コンテナは残す）
./vendor/bin/sail restart     # 再起動
./vendor/bin/sail ps          # 起動中のコンテナ確認
```

## artisan

```bash
./vendor/bin/sail artisan migrate              # マイグレーション実行
./vendor/bin/sail artisan migrate:fresh --seed # DB作り直し＋シード投入（開発中によく使う）
./vendor/bin/sail artisan migrate:rollback     # 直前のマイグレーションを戻す
./vendor/bin/sail artisan make:model Client -mf   # モデル＋マイグレーション＋Factory一括生成
./vendor/bin/sail artisan make:controller ClientController --resource
./vendor/bin/sail artisan tinker               # 対話シェル
./vendor/bin/sail artisan route:list           # ルート一覧確認
./vendor/bin/sail artisan queue:work           # キューワーカー起動（通知バッチのデバッグ時）
./vendor/bin/sail artisan schedule:work        # スケジューラをローカルで動かす（procedures:generate-upcoming等の確認用）
```

## composer / npm

```bash
./vendor/bin/sail composer require spatie/laravel-permission
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev     # Vite開発サーバー
./vendor/bin/sail npm run build   # 本番ビルド
```

## テスト（Pest）

```bash
./vendor/bin/sail artisan test                 # 全テスト実行
./vendor/bin/sail artisan test --filter=ClientTest
./vendor/bin/sail pest                         # pest直接実行（並列実行等オプションを使う場合）
./vendor/bin/sail pest --parallel
```

期限計算・周期ルールなど複雑なロジック（`procedure_types`の`recurrence_rule`展開処理等）は、実装後に必ずPestで単体テストを書く（企画書6章の方針）。

## Lint / フォーマット

```bash
./vendor/bin/sail pint            # PHPコードフォーマット（コミット前に実行）
./vendor/bin/sail pint --test     # フォーマット差分の確認のみ（変更しない）
```

## DB / コンテナ内シェル

```bash
./vendor/bin/sail mysql           # MySQL使用時、コンテナ内のmysqlクライアントに入る
./vendor/bin/sail shell           # アプリコンテナ内のbashに入る
./vendor/bin/sail root-shell      # root権限で入る（権限まわりのデバッグ時）
```

## Horizon（キュー監視、Phase4以降）

```bash
./vendor/bin/sail artisan horizon         # Horizon起動
# ブラウザで http://localhost/horizon にアクセス
```

## 初回セットアップ（sail:install がまだの場合）

```bash
./vendor/bin/sail composer install
./vendor/bin/sail artisan sail:install   # 対話式：mysql, redis, mailpit 等を選択
# → docker-compose.yml が生成される
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

このプロジェクトの用途（顧問先・タスクの本格的なリレーショナルデータ、キュー処理、通知）を踏まえると、`sail:install`では最低限 **mysql（またはpgsql）＋ redis ＋ mailpit** の選択を推奨する。sqliteのままでもMVPは動くが、Horizon/キュー監視を見せ場にする構想（企画書6章）があるならredisは早期に入れておいた方がよい。

## やってはいけないこと

- ホストに直接`php artisan ...`や`composer ...`を打たない（PHPバージョン不一致・拡張不足でハマる）
- `.env`の`DB_*`とdocker-compose.ymlのサービス名がズレていないか、DB接続エラー時はまずここを疑う
