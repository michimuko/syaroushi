mod api;
mod settings;

use serde::Serialize;
use settings::Settings;
use std::sync::Mutex;
use std::time::{Duration, Instant};
use tauri::menu::{Menu, MenuItem};
use tauri::tray::TrayIconBuilder;
use tauri::{AppHandle, Manager, State};
use tauri_plugin_autostart::ManagerExt;
use tauri_plugin_notification::NotificationExt;

#[derive(Default, Clone, Serialize)]
pub struct PollStatus {
    last_polled_at: Option<String>,
    last_error: Option<String>,
    last_count: usize,
}

pub struct AppState {
    status: Mutex<PollStatus>,
}

#[tauri::command]
fn load_settings(app: AppHandle) -> Settings {
    // 初回起動時のみ、自動起動を既定でオンにする（一般ユーザーはこの設定の意味を意識せず
    // 導入できるようにするため）。2回目以降はユーザー自身がチェックボックスで選んだ値を尊重する。
    if settings::is_first_run(&app) {
        let _ = app.autolaunch().enable();
    }
    settings::load(&app)
}

#[tauri::command]
fn save_settings(
    app: AppHandle,
    base_url: String,
    token: String,
    interval_minutes: u32,
) -> Result<(), String> {
    let settings = Settings {
        base_url: base_url.trim().to_string(),
        token: token.trim().to_string(),
        interval_minutes: interval_minutes.max(1),
    };
    settings::save(&app, &settings)
}

#[tauri::command]
fn get_status(state: State<AppState>) -> PollStatus {
    state.status.lock().unwrap().clone()
}

#[tauri::command]
async fn poll_now(app: AppHandle) -> PollStatus {
    do_poll(&app).await;
    app.state::<AppState>().status.lock().unwrap().clone()
}

#[tauri::command]
fn get_autostart(app: AppHandle) -> bool {
    app.autolaunch().is_enabled().unwrap_or(false)
}

#[tauri::command]
fn set_autostart(app: AppHandle, enabled: bool) -> Result<(), String> {
    let manager = app.autolaunch();
    let result = if enabled {
        manager.enable()
    } else {
        manager.disable()
    };
    result.map_err(|e| e.to_string())
}

async fn do_poll(app: &AppHandle) {
    let settings = settings::load(app);
    let state = app.state::<AppState>();

    if !settings.is_configured() {
        let mut status = state.status.lock().unwrap();
        status.last_error = Some("APIのURL・アクセストークンが未設定です".to_string());
        return;
    }

    let now = chrono::Local::now().format("%Y-%m-%d %H:%M:%S").to_string();

    match api::fetch_notifications(&settings.base_url, &settings.token).await {
        Ok(notifications) => {
            for n in &notifications {
                let title = format!("【期限まであと{}日】{}", n.lead_days, n.title);
                let body = format!("{}：期限日 {}", n.client_name, n.due_date);
                let _ = app.notification().builder().title(title).body(body).show();
            }
            let mut status = state.status.lock().unwrap();
            status.last_polled_at = Some(now);
            status.last_error = None;
            status.last_count = notifications.len();
        }
        Err(e) => {
            let mut status = state.status.lock().unwrap();
            status.last_polled_at = Some(now);
            status.last_error = Some(e);
        }
    }
}

#[cfg_attr(mobile, tauri::mobile_entry_point)]
pub fn run() {
    tauri::Builder::default()
        .plugin(tauri_plugin_opener::init())
        .plugin(tauri_plugin_notification::init())
        .plugin(tauri_plugin_autostart::init(
            tauri_plugin_autostart::MacosLauncher::LaunchAgent,
            None,
        ))
        .manage(AppState {
            status: Mutex::new(PollStatus::default()),
        })
        .invoke_handler(tauri::generate_handler![
            load_settings,
            save_settings,
            get_status,
            poll_now,
            get_autostart,
            set_autostart,
        ])
        .setup(|app| {
            let handle = app.handle().clone();

            let show_item =
                MenuItem::with_id(&handle, "show", "設定を開く", true, None::<&str>)?;
            let poll_item =
                MenuItem::with_id(&handle, "poll", "今すぐ確認", true, None::<&str>)?;
            let quit_item = MenuItem::with_id(&handle, "quit", "終了", true, None::<&str>)?;
            let menu = Menu::with_items(&handle, &[&show_item, &poll_item, &quit_item])?;

            TrayIconBuilder::new()
                .icon(app.default_window_icon().unwrap().clone())
                .menu(&menu)
                .show_menu_on_left_click(true)
                .on_menu_event(|app, event| match event.id.as_ref() {
                    "show" => {
                        if let Some(window) = app.get_webview_window("main") {
                            let _ = window.show();
                            let _ = window.set_focus();
                        }
                    }
                    "poll" => {
                        let handle = app.clone();
                        tauri::async_runtime::spawn(async move {
                            do_poll(&handle).await;
                        });
                    }
                    "quit" => {
                        app.exit(0);
                    }
                    _ => {}
                })
                .build(app)?;

            let poll_handle = handle.clone();
            tauri::async_runtime::spawn(async move {
                let mut last_poll: Option<Instant> = None;
                loop {
                    let settings = settings::load(&poll_handle);
                    let due = match last_poll {
                        None => true,
                        Some(t) => {
                            t.elapsed()
                                >= Duration::from_secs(settings.interval_minutes.max(1) as u64 * 60)
                        }
                    };
                    if settings.is_configured() && due {
                        do_poll(&poll_handle).await;
                        last_poll = Some(Instant::now());
                    }
                    tokio::time::sleep(Duration::from_secs(30)).await;
                }
            });

            Ok(())
        })
        .on_window_event(|window, event| {
            if let tauri::WindowEvent::CloseRequested { api, .. } = event {
                let _ = window.hide();
                api.prevent_close();
            }
        })
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
