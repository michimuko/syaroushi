use serde::Deserialize;

#[derive(Debug, Clone, Deserialize)]
pub struct TaskNotification {
    #[allow(dead_code)]
    pub procedure_task_id: u64,
    pub title: String,
    pub client_name: String,
    #[allow(dead_code)]
    pub procedure_type: String,
    pub due_date: String,
    pub lead_days: i64,
    #[allow(dead_code)]
    pub url: String,
}

#[derive(Debug, Deserialize)]
struct NotificationsResponse {
    notifications: Vec<TaskNotification>,
}

pub async fn fetch_notifications(
    base_url: &str,
    token: &str,
) -> Result<Vec<TaskNotification>, String> {
    let url = format!(
        "{}/api/desktop/notifications",
        base_url.trim_end_matches('/')
    );

    let client = reqwest::Client::new();
    let res = client
        .get(&url)
        .bearer_auth(token)
        .header("Accept", "application/json")
        .send()
        .await
        .map_err(|e| format!("通信エラー: {e}"))?;

    if !res.status().is_success() {
        return Err(format!("APIエラー（HTTP {}）", res.status()));
    }

    let body: NotificationsResponse = res
        .json()
        .await
        .map_err(|e| format!("レスポンス解析エラー: {e}"))?;

    Ok(body.notifications)
}
