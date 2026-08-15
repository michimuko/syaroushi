<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<style>
    @font-face {
        font-family: 'IPAGothic';
        font-weight: normal;
        src: url('{{ $fontPath }}');
    }

    @font-face {
        font-family: 'IPAGothic';
        font-weight: bold;
        src: url('{{ $fontPath }}');
    }

    * {
        font-family: 'IPAGothic', sans-serif;
    }

    body {
        font-size: 11px;
        color: #1f2937;
    }

    .header {
        border-bottom: 2px solid #1f2937;
        padding-bottom: 8px;
        margin-bottom: 16px;
    }

    .office-name {
        font-size: 10px;
        color: #6b7280;
        text-align: right;
    }

    .title {
        font-size: 18px;
        font-weight: bold;
        margin: 4px 0;
    }

    .meta {
        font-size: 11px;
        color: #374151;
        margin-bottom: 2px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
    }

    th, td {
        border: 1px solid #d1d5db;
        padding: 6px 8px;
        text-align: left;
        font-size: 10px;
    }

    th {
        background-color: #f3f4f6;
        font-weight: bold;
    }

    .status {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 9px;
    }

    .status-completed { background-color: #dcfce7; color: #166534; }
    .status-submitted { background-color: #dbeafe; color: #1e40af; }
    .status-documents_collected { background-color: #e0e7ff; color: #3730a3; }
    .status-in_progress { background-color: #fef9c3; color: #854d0e; }
    .status-not_started { background-color: #f3f4f6; color: #4b5563; }

    .comment-box {
        margin-top: 20px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        padding: 10px 12px;
    }

    .comment-title {
        font-weight: bold;
        margin-bottom: 6px;
    }

    .footer {
        margin-top: 24px;
        font-size: 9px;
        color: #9ca3af;
        text-align: right;
    }

    .empty {
        color: #6b7280;
        padding: 12px 0;
    }
</style>
</head>
<body>
    <div class="header">
        <div class="office-name">{{ $office->name }}</div>
        <div class="title">顧問先向け進捗レポート</div>
        <div class="meta">顧問先：{{ $client->name }}</div>
        <div class="meta">対象期間：{{ $periodStart->format('Y年n月j日') }} 〜 {{ $periodEnd->format('Y年n月j日') }}</div>
    </div>

    @if ($tasks->isEmpty())
        <p class="empty">対象期間内の手続きはありません。</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>手続き名</th>
                    <th>期限日</th>
                    <th>ステータス</th>
                    <th>完了日</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                    <tr>
                        <td>{{ $task->title }}</td>
                        <td>{{ $task->due_date->format('Y/m/d') }}</td>
                        <td><span class="status status-{{ $task->status->value }}">{{ $task->status->label() }}</span></td>
                        <td>{{ $task->completed_at?->format('Y/m/d') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($comment)
        <div class="comment-box">
            <div class="comment-title">所感</div>
            <div>{!! nl2br(e($comment)) !!}</div>
        </div>
    @endif

    <div class="footer">
        作成日：{{ $generatedAt->format('Y年n月j日') }}　作成者：{{ $generatedByName }}
    </div>
</body>
</html>
