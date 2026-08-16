# デスクトップ通知アプリ（Tauri）

業務進捗・期限管理SaaSの期限リマインドを、OSの通知センターにプッシュする常駐トレイアプリ。
Webアプリの「設定 &gt; デスクトップ通知アプリ」（`/settings/desktop-app`）で発行したURL・アクセストークンを使い、
`GET /api/desktop/notifications` を定期ポーリングして未配信の通知を表示する。

サーバー側の実装（トークン発行・ポーリングAPI）は `app/Http/Controllers/DesktopAppTokenController.php` /
`app/Http/Controllers/Api/DesktopNotificationController.php` を参照。

## 構成

- `src/` … 設定画面（トークン・確認間隔・自動起動のON/OFFを保存するだけの単純なフォーム）
- `src-tauri/src/settings.rs` … 設定をOSのアプリ設定ディレクトリに保存
- `src-tauri/src/api.rs` … `GET /api/desktop/notifications` の呼び出し
- `src-tauri/src/lib.rs` … トレイアイコン・バックグラウンドポーリングループ・OS通知表示

ウィンドウを閉じてもプロセスは終了せず（`prevent_close`）、トレイに常駐し続ける。

## 開発

```bash
npm install
npm run tauri dev
```

## 配布（利用者向け）

利用者（社労士事務所のスタッフ）は自分でビルドする必要はない。Webアプリの
「設定 &gt; デスクトップ通知アプリ」（`/settings/desktop-app`）から、OS向けのビルド済み
インストーラーをダウンロードしてそのまま実行するだけでインストールできる。

インストーラーの実体は `.github/workflows/desktop-app-release.yml` がタグpush
（`desktop-app-v*`）をトリガーに `tauri-apps/tauri-action` でWindows/macOS/Linux向けに
自動ビルドし、GitHub Releasesへ公開している。このリポジトリはprivateのため、Laravel側
（`App\Services\DesktopAppReleaseService` / `DesktopAppDownloadController`）がサーバーの
GitHubトークン（`GITHUB_TOKEN`環境変数）を使って最新リリースのアセット一覧取得・
ダウンロードの中継を行う。

新しいバージョンをリリースする手順：

1. `desktop-app/package.json` と `desktop-app/src-tauri/tauri.conf.json` の `version` を更新する
2. `git tag desktop-app-v0.2.0 && git push origin desktop-app-v0.2.0`
3. Actionsの完了後、Webアプリの設定画面に反映される（最大10分キャッシュ、`DesktopAppReleaseService`）

## 開発者向けビルド

```bash
npm install
npm run tauri build
```

`src-tauri/target/release/bundle/` 以下にOSごとのインストーラー（Linuxは `.deb`/`.AppImage` 等、
Windowsは `.msi`/`.exe`、macOSは `.app`/`.dmg`）が生成される。ビルドにはRustツールチェイン
（`rustup`）とOSごとのTauri前提パッケージ（Linuxなら `libwebkit2gtk`, `libappindicator` 等）が必要。
詳細は [Tauriの公式セットアップ手順](https://tauri.app/start/prerequisites/) を参照。

## 動作確認について

このリポジトリの開発コンテナには実ディスプレイ（X/Wayland）が無いため、`cargo build --release`
でのビルド成功は確認済みだが、トレイアイコン表示やOS通知の表示自体はGUI環境で目視確認する必要がある。
実機で確認する際は、Webアプリの設定画面で発行したURL・トークンを入力して「今すぐ確認」を押し、
通知が届くことを確認すること。
