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

## ビルド

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
