# 本番デプロイ手順（さくらのVPS 1GBプラン）

2026-08-17時点の構成方針。初回リリースはコストを抑えるため**さくらのVPS 1GBプラン**（メモリ1GB。
月額は変動するため契約時に公式サイトで要確認）、Ubuntu 24.04 LTS、単一インスタンス構成
（Webサーバー・MySQL・キューワーカーを1台に同居させる）。

このディレクトリには実際の設定ファイルのテンプレートを置いている（`sharoushi.korotane.site`等は要書き換え）。
korotaneブランド配下で複数のtoB DX SaaSをサブドメインで展開する想定のため、本アプリはドメイン直下
（`korotane.site`）ではなくサブドメイン（`sharoushi.korotane.site`）で運用する。
1GBプランではメモリに余裕がないため、キャッシュ/セッション/キューは全て`database`ドライバとし、
Redisは使わない（`.env`参照）。事務所数が増えて2GBプランへスケールアップする場合は
「12. 2GBプランへスケールアップする場合」を参照。

| ファイル | 配置先 | 用途 |
|---|---|---|
| `nginx/syaroushi.conf` | `/etc/nginx/sites-available/syaroushi.conf` | 共通 |
| `php/pool-syaroushi.conf` | `/etc/php/8.3/fpm/pool.d/syaroushi.conf` | 1GBプラン用（デフォルト） |
| `php/pool-syaroushi-2gb.conf` | 同上 | 2GBプランへスケールアップ後に差し替え |
| `mysql/syaroushi.cnf` | `/etc/mysql/mysql.conf.d/syaroushi.cnf` | 1GBプラン用（デフォルト） |
| `mysql/syaroushi-2gb.cnf` | 同上 | 2GBプランへスケールアップ後に差し替え |
| `supervisor/syaroushi-worker.conf` | `/etc/supervisor/conf.d/syaroushi-worker.conf` | 2GBプラン以降でのみ使用（1GBではcron方式） |
| `env.production.example` | `/var/www/syaroushi/.env`（値を埋めてから） | 共通 |

## 前提として先にやっておくこと

- **ドメイン取得＋DNS設定**（VPSのIPにAレコードを向ける）
- **Cloudflare R2バケットの作成**（[プロジェクトの方針](../CLAUDE.md)どおりS3互換ストレージが書類チェックリスト・PDFレポート機能に必須）。CloudflareダッシュボードでR2を有効化し、バケット作成後「S3 API」からAccess Key ID・Secret Access Key・エンドポイントURLを控えておく
- **メール送信サービスの契約**（Resend・SendGrid等）。`procedures:send-reminders`バッチのメール通知が届かないと本製品の中核機能が動かないため必須

## 1. サーバー初期設定

```bash
apt update && apt upgrade -y
adduser deploy && usermod -aG sudo deploy
# 以降deployユーザーで作業する想定（rootログイン直作業は避ける）

# swap追加（1GBメモリだとMySQL+PHP-FPM+cronワーカー同居時にOOMの安全弁が無いと危険。
# ディスクは余裕があるためメモリの2倍程度確保しておく）
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab

ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw enable
```

## 2. ミドルウェアのインストール

```bash
# PHP 8.3
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php
apt update
apt install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl
# Redisは使わない構成（database driver）のためphp8.3-redisは不要

# MySQL
apt install -y mysql-server

# Nginx / supervisor / Node（ビルド用）
apt install -y nginx supervisor
curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

## 3. MySQLセットアップ

```bash
mysql_secure_installation

mysql -u root -p <<'SQL'
CREATE DATABASE syaroushi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'syaroushi'@'localhost' IDENTIFIED BY '生成したパスワード';
GRANT ALL PRIVILEGES ON syaroushi.* TO 'syaroushi'@'localhost';
FLUSH PRIVILEGES;
SQL

cp deploy/mysql/syaroushi.cnf /etc/mysql/mysql.conf.d/syaroushi.cnf
systemctl restart mysql
```

## 4. アプリケーション配置

```bash
mkdir -p /var/www/syaroushi
chown deploy:deploy /var/www/syaroushi
# deployユーザーで
git clone <このリポジトリのURL> /var/www/syaroushi
cd /var/www/syaroushi

composer install --no-dev --optimize-autoloader
npm ci
npm run build

cp deploy/env.production.example .env
# .envを実際の値で埋める（DB_PASSWORD, AWS_*, MAIL_*, VAPID_* 等）

php artisan key:generate
php artisan webpush:vapid
# 出力されたVAPID_PUBLIC_KEY / VAPID_PRIVATE_KEYを.envに反映（開発環境の鍵は使い回さない）

php artisan migrate --force

# database/seeders/DatabaseSeeder.php はダミーデータ（ClientSeeder等）を含み開発専用のため
# 本番では絶対に db:seed のフル実行をしない。以下の2つだけを個別に実行する。
php artisan db:seed --class=ProcedureTypeSeeder --force
# procedure_typesマスタ（法定手続き十数種類）を投入
php artisan db:seed --class=PlatformAdminSeeder --force
# .envのPLATFORM_ADMIN_EMAIL/PASSWORDから運営者アカウント（/admin ログイン用）を作成

php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R www-data:www-data /var/www/syaroushi/storage /var/www/syaroushi/bootstrap/cache
```

## 5. PHP-FPM / Nginx

```bash
cp deploy/php/pool-syaroushi.conf /etc/php/8.3/fpm/pool.d/syaroushi.conf
# デフォルトの www.conf プールと衝突しないよう、必要なら /etc/php/8.3/fpm/pool.d/www.conf を無効化
systemctl restart php8.3-fpm

cp deploy/nginx/syaroushi.conf /etc/nginx/sites-available/syaroushi.conf
ln -s /etc/nginx/sites-available/syaroushi.conf /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# TLS証明書取得（DNS反映後）
apt install -y certbot python3-certbot-nginx
certbot --nginx -d sharoushi.korotane.site
```

## 6. キューワーカー（1GBプランではcron方式）

1GBプランではsupervisorによる常駐ワーカー（常時~80MBのメモリを占有）を使わず、
cronで`queue:work --stop-when-empty`を毎分起動し、キューが空になったら即終了する方式にする
（アイドル時はメモリを他プロセスに譲れる）。手順は次の「7. スケジューラ（cron）」で
`schedule:run`と合わせてまとめて設定する。

現状のコード（2026-08-17時点）では`procedures:send-reminders`等の通知は`ShouldQueue`を
使わず同期送信のため、`jobs`テーブルは基本的に空でqueue:workはほぼ何もせず毎分即終了する
（実質メモリ負荷なし）。将来PDF生成やインポート処理等でキュー投入する機能を追加した時に
そのまま拾えるよう、cronの仕組みだけ先に用意している。

2GBプランへスケールアップした場合は、即時処理が必要になった時点で
`deploy/supervisor/syaroushi-worker.conf`による常駐ワーカーに切り替える
（「12. 2GBプランへスケールアップする場合」参照）。

## 7. スケジューラ（cron）

Laravelのバッチ（`procedures:generate-upcoming`／`procedures:send-reminders`／
`documents:notify-retention-expiry`／`imports:cleanup-stale-files`／`billing:generate-invoices`、
`routes/console.php`参照）は`schedule:run`が1分おきに呼ばれて初めて実行される。

```bash
crontab -u www-data -e
```

以下の2行を追加（`queue:work`は将来キュー投入する機能が増えた時のための予備で、現状は毎分ほぼ即終了する）：

```
* * * * * cd /var/www/syaroushi && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/syaroushi && php artisan queue:work --stop-when-empty --max-time=50 --tries=3 >> /var/www/syaroushi/storage/logs/worker.log 2>&1
```

`--max-time=50`は次の分のcron実行と重ならないようにするための安全弁。

## 8. DBバックアップ

`deploy/backup.sh`が`mysqldump`→gzip→14日ローテーションを行う（顧問先の個人情報を含むため
`chmod 700`のディレクトリに`chmod 600`で保存する）。

```bash
crontab -u root -e
```

以下を追加（root権限でmysqldump・バックアップディレクトリ作成を行うため`www-data`ではなく`root`のcronに登録する）：

```
0 3 * * * /var/www/syaroushi/deploy/backup.sh >> /var/log/syaroushi-backup.log 2>&1
```

同一VPSが壊れた場合に備え、可能であればCloudflare R2への自動コピーも設定しておく
（バックアップ専用の別バケットを用意し、スクリプト内コメントの手順で`r2-backup`プロファイルを作成、
`.env`に`BACKUP_R2_BUCKET`を追加すると自動的に有効化される）。

**リストア手順**（緊急時のみ）：

```bash
gunzip < /var/backups/syaroushi/db-YYYYMMDD-HHMMSS.sql.gz | mysql -u syaroushi -p syaroushi
```

## 9. エラー監視（Sentry）

`sentry/sentry-laravel`は導入・配線済み（`bootstrap/app.php`、`config/sentry.php`）。
[sentry.io](https://sentry.io) で無料のDeveloperプラン（5,000エラー/月）でプロジェクトを作成し
（Platform: Laravel）、発行されたDSNを`.env`の`SENTRY_LARAVEL_DSN`に設定するだけで有効になる。
DSN未設定の間はSDKは自動的に無効化されるため、後回しにしても他機能に影響しない。

## 10. メール送信（Resend）

`MAIL_MAILER=resend`はLaravel標準サポート・依存パッケージ導入済み。
[resend.com](https://resend.com) でアカウント作成→送信元ドメインのDNS認証（SPF/DKIM、
Resend側の管理画面に表示されるレコードをドメインのDNS設定に追加）→API Key発行の順で進め、
`.env`の`RESEND_API_KEY`に設定する。無料枠は3,000通/月・100通/日。

## 11. 動作確認チェックリスト

- [ ] `https://sharoushi.korotane.site` にアクセスしてログイン画面が表示される
- [ ] `/register` から事務所（office）＋ownerアカウントを新規作成できる（本番用の最初の事務所はここから作る。ダミーseederは使わない）
- [ ] `php artisan schedule:list` でバッチが正しく登録されている
- [ ] 顧問先を1件登録し、手続きタスクが自動生成されることを確認
- [ ] 書類アップロード→署名付きURLでのダウンロードがR2経由で動作する
- [ ] テスト用に期限が近いタスクを作り、`procedures:send-reminders`実行でメールが届く（1GBプランではcron起動の`queue:work`が処理するため、届くまで最大1分程度かかる想定）
- [ ] `crontab -u www-data -l`でschedule:run・queue:workの2行が登録されている
- [ ] デスクトップ通知アプリの設定画面（`/settings/desktop-app`）からトークン発行できる

## 更新（デプロイ）時の手順

```bash
cd /var/www/syaroushi
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

cron方式（1GBプラン）は次回の`queue:work`起動時（最大1分後）に自動的に新しいコードで動くため、
再起動操作は不要。2GBプランでsupervisor常駐にしている場合のみ
`supervisorctl restart syaroushi-worker:*`を追加する。

## メモリ予算の目安（1GB中）

| プロセス | 目安 |
|---|---|
| OS・その他常駐 | ~150MB |
| MySQL（`innodb_buffer_pool_size=96M`設定後） | ~180MB |
| PHP-FPM（`pm.max_children=3`、実行時のみ最大） | ~最大384MB |
| queue:work（cron起動） | ほぼ0（現状キューが空のため毎分即終了。将来ジョブが増えたら実行時のみ~80MB） |
| Nginx | ~30MB |
| 空き・swap待避余地 | 残り |

実測でOOMが発生する場合は`pm.max_children`を先に2まで下げる（Nginx/MySQLより優先して絞ってよい）。
それでも厳しい場合は2GBプランへのスケールアップを検討する（下記12.参照）。

## 12. 2GBプランへスケールアップする場合

事務所数が増えてきて1GBプランの余裕がなくなってきたら、さくらのVPSコントロールパネルの
「スケールアップ」機能でプラン変更する（同一ディスク・同一IPのままCPU/メモリだけ増える。
変更中は数分間サーバー停止が必要）。プラン変更後、以下を差し替える：

```bash
# PHP-FPM
cp deploy/php/pool-syaroushi-2gb.conf /etc/php/8.3/fpm/pool.d/syaroushi.conf
systemctl restart php8.3-fpm

# MySQL
cp deploy/mysql/syaroushi-2gb.cnf /etc/mysql/mysql.conf.d/syaroushi.cnf
systemctl restart mysql

# キューワーカーをcron方式から常駐supervisor方式に切り替える
crontab -u www-data -e
# queue:workの行（`* * * * * ... queue:work --stop-when-empty ...`）を削除

cp deploy/supervisor/syaroushi-worker.conf /etc/supervisor/conf.d/syaroushi-worker.conf
supervisorctl reread
supervisorctl update
supervisorctl start syaroushi-worker:*
supervisorctl status
```

ディスク容量はスケールアップでは自動拡張されないため、必要なら別途パーティション拡張の手順を
公式マニュアルで確認する（書類ファイルはR2に置く設計のため通常は不要）。
