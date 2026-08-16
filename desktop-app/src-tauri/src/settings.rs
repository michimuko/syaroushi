use serde::{Deserialize, Serialize};
use std::path::PathBuf;
use tauri::{AppHandle, Manager};

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Settings {
    #[serde(default)]
    pub base_url: String,
    #[serde(default)]
    pub token: String,
    #[serde(default = "default_interval")]
    pub interval_minutes: u32,
}

fn default_interval() -> u32 {
    5
}

impl Default for Settings {
    fn default() -> Self {
        Self {
            base_url: String::new(),
            token: String::new(),
            interval_minutes: default_interval(),
        }
    }
}

impl Settings {
    pub fn is_configured(&self) -> bool {
        !self.base_url.trim().is_empty() && !self.token.trim().is_empty()
    }
}

fn settings_path(app: &AppHandle) -> Result<PathBuf, String> {
    let dir = app.path().app_config_dir().map_err(|e| e.to_string())?;
    std::fs::create_dir_all(&dir).map_err(|e| e.to_string())?;
    Ok(dir.join("settings.json"))
}

pub fn load(app: &AppHandle) -> Settings {
    let path = match settings_path(app) {
        Ok(p) => p,
        Err(_) => return Settings::default(),
    };
    match std::fs::read_to_string(&path) {
        Ok(content) => serde_json::from_str(&content).unwrap_or_default(),
        Err(_) => Settings::default(),
    }
}

/// settings.jsonがまだ無い＝アプリの初回起動かどうかを判定する。
/// 初回起動時のみ自動起動を既定で有効化する（2回目以降はユーザーの選択を尊重する）ために使う。
pub fn is_first_run(app: &AppHandle) -> bool {
    match settings_path(app) {
        Ok(path) => !path.exists(),
        Err(_) => false,
    }
}

pub fn save(app: &AppHandle, settings: &Settings) -> Result<(), String> {
    let path = settings_path(app)?;
    let content = serde_json::to_string_pretty(settings).map_err(|e| e.to_string())?;
    std::fs::write(&path, content).map_err(|e| e.to_string())
}
