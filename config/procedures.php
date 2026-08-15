<?php

return [

    /*
    |--------------------------------------------------------------------------
    | タスク自動生成の先読み期間（日数）
    |--------------------------------------------------------------------------
    |
    | procedures:generate-upcoming バッチが、今日からこの日数先までの期限日を
    | 対象にprocedure_tasksを生成する（企画書8章）。
    |
    */
    'generate_lookahead_days' => env('PROCEDURES_GENERATE_LOOKAHEAD_DAYS', 120),

];
