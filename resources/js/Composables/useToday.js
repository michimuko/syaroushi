// <input type="date">に渡す「今日の日付」文字列。toISOString()はUTC変換されるため、
// 日本時間の深夜0〜9時台だと前日の日付になってしまう。ブラウザのローカル日時から
// 年月日を直接組み立てることでこのずれを避ける。
export function todayDateString() {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}
