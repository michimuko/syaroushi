<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * デスクトップ通知アプリ（Tauri、企画書5-C Level2）がバックグラウンドでポーリングするAPI。
 * 「メール／Web Push向けにすでに生成済みのリマインドのうち、まだこのAPI経由で
 * 配信していないもの」をNotificationLogのchannel='desktop'行の有無で判定して返す
 * （procedures:send-remindersバッチ自体には手を入れず、既存の通知ログを再利用する）。
 *
 * 注意：この経路はSanctumトークン認証（'web'セッションを持たない）でアクセスされるため、
 * NotificationTask等に付与されたGlobal Scope（OfficeScope）は`auth()`のデフォルトガードが
 * 'web'であることから機能しない（App\Models\Scopes\OfficeScope参照）。そのためテナント分離は
 * ここでは「常にAuth::user()（＝トークンの持ち主）のrecipient_user_idに一致する行のみを扱う」
 * ことで担保する。recipient_user_idはリマインド送信時点でタスクと同じ事務所のユーザーに
 * しか設定されないため、これだけで他事務所のデータへは到達し得ない。
 */
class DesktopNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $alreadyDelivered = NotificationLog::query()
            ->where('recipient_user_id', $user->id)
            ->where('channel', NotificationChannel::Desktop)
            ->get(['procedure_task_id', 'lead_days'])
            ->map(fn (NotificationLog $log) => "{$log->procedure_task_id}:{$log->lead_days}")
            ->all();

        $pending = NotificationLog::query()
            ->where('recipient_user_id', $user->id)
            ->whereIn('channel', [NotificationChannel::Email, NotificationChannel::WebPush])
            ->with(['procedureTask.client', 'procedureTask.procedureType'])
            ->orderBy('sent_at')
            ->get()
            ->unique(fn (NotificationLog $log) => "{$log->procedure_task_id}:{$log->lead_days}")
            ->reject(fn (NotificationLog $log) => in_array(
                "{$log->procedure_task_id}:{$log->lead_days}",
                $alreadyDelivered,
                true,
            ))
            ->values();

        $pending->each(function (NotificationLog $log) use ($user): void {
            NotificationLog::create([
                'office_id' => $log->office_id,
                'procedure_task_id' => $log->procedure_task_id,
                'recipient_user_id' => $user->id,
                'channel' => NotificationChannel::Desktop,
                'lead_days' => $log->lead_days,
                'sent_at' => now(),
            ]);
        });

        return response()->json([
            'notifications' => $pending->map(fn (NotificationLog $log) => [
                'procedure_task_id' => $log->procedureTask->id,
                'title' => $log->procedureTask->title,
                'client_name' => $log->procedureTask->client->name,
                'procedure_type' => $log->procedureTask->procedureType->name,
                'due_date' => $log->procedureTask->due_date->toDateString(),
                'lead_days' => $log->lead_days,
                'url' => route('tasks.edit', $log->procedureTask),
            ])->values(),
        ]);
    }
}
