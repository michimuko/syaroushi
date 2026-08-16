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

/// 通信自体が失敗した場合（サーバーに届く前のエラー）向けの案内文。
/// 素人が読んでも次にとるべき行動が分かる一言を先頭に置き、原因の特定に必要な
/// 技術的な詳細（reqwestのエラー内容）は正確性を保つためにあえて残し、末尾に添える。
fn friendly_send_error(e: &reqwest::Error) -> String {
    let hint = if e.is_timeout() {
        "サーバーへの接続がタイムアウトしました。ネットワーク環境をご確認のうえ、しばらくしてから再度お試しください。"
    } else if e.is_connect() {
        "サーバーに接続できませんでした。「事務所のURL」が正しいか、パソコンがインターネットに接続されているかご確認ください。"
    } else {
        "サーバーとの通信中にエラーが発生しました。ネットワーク環境をご確認のうえ、しばらくしてから再度お試しください。"
    };
    format!("{hint}（詳細: {e}）")
}

/// サーバーには届いたがエラーが返ってきた場合（HTTPステータスエラー）向けの案内文。
fn friendly_status_error(status: reqwest::StatusCode) -> String {
    let hint = match status.as_u16() {
        401 | 403 => "アクセストークンが正しくないか、失効している可能性があります。Webアプリの設定画面でトークンを再発行し、貼り付け直してください。",
        404 => "「事務所のURL」が正しくない可能性があります。ブラウザで開いていたページのURLではなく、事務所のトップURL（例: https://example.com）のみを入力してください。",
        408 | 429 => "サーバーが混み合っています。しばらくしてから再度お試しください。",
        500..=599 => "サーバー側で問題が発生しています。しばらくしてから再度お試しください。解消しない場合は事務所の管理者にご確認ください。",
        _ => "サーバーとの通信でエラーが発生しました。「事務所のURL」の入力内容をご確認のうえ、しばらくしてから再度お試しください。",
    };
    format!("{hint}（詳細: HTTP {status}）")
}

/// レスポンスは受け取れたが内容を解釈できなかった場合向けの案内文。
fn friendly_parse_error(e: &reqwest::Error) -> String {
    format!(
        "サーバーからの応答を正しく読み取れませんでした。アプリが古い可能性があるので、最新版に更新してから再度お試しください。（詳細: {e}）"
    )
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
        .map_err(|e| friendly_send_error(&e))?;

    if !res.status().is_success() {
        return Err(friendly_status_error(res.status()));
    }

    let body: NotificationsResponse = res.json().await.map_err(|e| friendly_parse_error(&e))?;

    Ok(body.notifications)
}

#[cfg(test)]
mod tests {
    use super::*;
    use reqwest::StatusCode;

    #[test]
    fn friendly_status_error_hints_at_reissuing_token_on_auth_failure() {
        let msg = friendly_status_error(StatusCode::UNAUTHORIZED);
        assert!(msg.contains("トークンを再発行"));
        assert!(msg.contains("HTTP 401"));

        let msg = friendly_status_error(StatusCode::FORBIDDEN);
        assert!(msg.contains("トークンを再発行"));
    }

    #[test]
    fn friendly_status_error_hints_at_wrong_url_on_not_found() {
        let msg = friendly_status_error(StatusCode::NOT_FOUND);
        assert!(msg.contains("事務所のURL"));
        assert!(msg.contains("HTTP 404"));
    }

    #[test]
    fn friendly_status_error_hints_at_retry_on_server_error() {
        let msg = friendly_status_error(StatusCode::INTERNAL_SERVER_ERROR);
        assert!(msg.contains("しばらくしてから再度"));
        assert!(msg.contains("HTTP 500"));

        let msg = friendly_status_error(StatusCode::SERVICE_UNAVAILABLE);
        assert!(msg.contains("しばらくしてから再度"));
    }

    #[test]
    fn friendly_status_error_falls_back_for_unmapped_status() {
        let msg = friendly_status_error(StatusCode::UNPROCESSABLE_ENTITY);
        assert!(msg.contains("HTTP 422"));
    }
}
