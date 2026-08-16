import { invoke } from "@tauri-apps/api/core";

interface Settings {
  base_url: string;
  token: string;
  interval_minutes: number;
}

interface PollStatus {
  last_polled_at: string | null;
  last_error: string | null;
  last_count: number;
}

const form = document.querySelector<HTMLFormElement>("#settings-form")!;
const baseUrlInput = document.querySelector<HTMLInputElement>("#base-url")!;
const tokenInput = document.querySelector<HTMLInputElement>("#token")!;
const intervalInput = document.querySelector<HTMLInputElement>("#interval")!;
const autostartInput = document.querySelector<HTMLInputElement>("#autostart")!;
const pollNowBtn = document.querySelector<HTMLButtonElement>("#poll-now-btn")!;
const statusEl = document.querySelector<HTMLDivElement>("#status")!;

function renderStatus(status: PollStatus) {
  const lines: string[] = [];
  lines.push(
    status.last_polled_at
      ? `最終確認: ${status.last_polled_at}（${status.last_count}件の通知）`
      : "まだ確認していません",
  );
  if (status.last_error) {
    lines.push(`エラー: ${status.last_error}`);
  }
  statusEl.textContent = lines.join(" / ");
  statusEl.classList.toggle("error", !!status.last_error);
}

async function loadInitialState() {
  const settings = await invoke<Settings>("load_settings");
  baseUrlInput.value = settings.base_url;
  tokenInput.value = settings.token;
  intervalInput.value = String(settings.interval_minutes);

  const autostartEnabled = await invoke<boolean>("get_autostart");
  autostartInput.checked = autostartEnabled;

  const status = await invoke<PollStatus>("get_status");
  renderStatus(status);
}

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  await invoke("save_settings", {
    baseUrl: baseUrlInput.value,
    token: tokenInput.value,
    intervalMinutes: Number(intervalInput.value) || 5,
  });
  await invoke("set_autostart", { enabled: autostartInput.checked });
  statusEl.textContent = "保存しました";
  statusEl.classList.remove("error");
});

pollNowBtn.addEventListener("click", async () => {
  pollNowBtn.disabled = true;
  try {
    const status = await invoke<PollStatus>("poll_now");
    renderStatus(status);
  } finally {
    pollNowBtn.disabled = false;
  }
});

loadInitialState();
