<?php

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーションメッセージ
    |--------------------------------------------------------------------------
    |
    | ここではバリデータクラスが使用するデフォルトのエラーメッセージを定義する。
    | サイズ系のルールなど複数バリエーションを持つものもある。必要に応じて
    | 自由に文言を調整してよい。
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeには有効なURLを指定してください。',
    'after' => ':attributeには:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeには:date以降の日付を指定してください。',
    'alpha' => ':attributeには英字のみ使用できます。',
    'alpha_dash' => ':attributeには英数字・ハイフン（-）・アンダースコア（_）のみ使用できます。',
    'alpha_num' => ':attributeには英数字のみ使用できます。',
    'any_of' => ':attributeの形式が正しくありません。',
    'array' => ':attributeには配列を指定してください。',
    'array_keys' => ':attributeには次のキーのみ含めてください：:values。',
    'ascii' => ':attributeには半角の英数字・記号のみ使用できます。',
    'base64' => ':attributeには有効なBase64文字列を指定してください。',
    'before' => ':attributeには:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeには:date以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeの項目数は:min〜:max個の範囲で指定してください。',
        'file' => ':attributeのファイルサイズは:min〜:maxキロバイトの範囲で指定してください。',
        'numeric' => ':attributeは:min〜:maxの範囲で指定してください。',
        'string' => ':attributeは:min〜:max文字の範囲で指定してください。',
    ],
    'boolean' => ':attributeにはtrueまたはfalseを指定してください。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attribute（確認用）が一致しません。',
    'contains' => ':attributeに必要な値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeには有効な日付を指定してください。',
    'date_equals' => ':attributeには:dateと同じ日付を指定してください。',
    'date_format' => ':attributeは:formatの形式で指定してください。',
    'decimal' => ':attributeは小数点以下:decimal桁で指定してください。',
    'declined' => ':attributeを非承認にしてください。',
    'declined_if' => ':otherが:valueの場合、:attributeを非承認にしてください。',
    'different' => ':attributeと:otherには異なる値を指定してください。',
    'digits' => ':attributeは:digits桁で指定してください。',
    'digits_between' => ':attributeは:min〜:max桁の範囲で指定してください。',
    'dimensions' => ':attributeの画像サイズが正しくありません。',
    'distinct' => ':attributeに重複した値があります。',
    'doesnt_contain' => ':attributeには次のいずれも含めないでください：:values。',
    'doesnt_end_with' => ':attributeは次のいずれかで終わらないようにしてください：:values。',
    'doesnt_start_with' => ':attributeは次のいずれかで始まらないようにしてください：:values。',
    'email' => ':attributeには有効なメールアドレスを指定してください。',
    'encoding' => ':attributeは:encodingでエンコードしてください。',
    'ends_with' => ':attributeは次のいずれかで終わるようにしてください：:values。',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'extensions' => ':attributeには次のいずれかの拡張子を指定してください：:values。',
    'file' => ':attributeにはファイルを指定してください。',
    'filled' => ':attributeには値を指定してください。',
    'gt' => [
        'array' => ':attributeの項目数は:value個より多く指定してください。',
        'file' => ':attributeのファイルサイズは:valueキロバイトより大きくしてください。',
        'numeric' => ':attributeは:valueより大きい値を指定してください。',
        'string' => ':attributeは:value文字より長く指定してください。',
    ],
    'gte' => [
        'array' => ':attributeの項目数は:value個以上指定してください。',
        'file' => ':attributeのファイルサイズは:valueキロバイト以上にしてください。',
        'numeric' => ':attributeは:value以上の値を指定してください。',
        'string' => ':attributeは:value文字以上で指定してください。',
    ],
    'hex_color' => ':attributeには有効な16進数カラーコードを指定してください。',
    'image' => ':attributeには画像ファイルを指定してください。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在しません。',
    'in_array_keys' => ':attributeには次のキーのうち少なくとも1つを含めてください：:values。',
    'integer' => ':attributeには整数を指定してください。',
    'ip' => ':attributeには有効なIPアドレスを指定してください。',
    'ipv4' => ':attributeには有効なIPv4アドレスを指定してください。',
    'ipv6' => ':attributeには有効なIPv6アドレスを指定してください。',
    'json' => ':attributeには有効なJSON文字列を指定してください。',
    'list' => ':attributeにはリスト形式を指定してください。',
    'lowercase' => ':attributeは小文字で指定してください。',
    'lt' => [
        'array' => ':attributeの項目数は:value個より少なく指定してください。',
        'file' => ':attributeのファイルサイズは:valueキロバイトより小さくしてください。',
        'numeric' => ':attributeは:valueより小さい値を指定してください。',
        'string' => ':attributeは:value文字より短く指定してください。',
    ],
    'lte' => [
        'array' => ':attributeの項目数は:value個以下にしてください。',
        'file' => ':attributeのファイルサイズは:valueキロバイト以下にしてください。',
        'numeric' => ':attributeは:value以下の値を指定してください。',
        'string' => ':attributeは:value文字以下で指定してください。',
    ],
    'mac_address' => ':attributeには有効なMACアドレスを指定してください。',
    'max' => [
        'array' => ':attributeの項目数は:max個以下にしてください。',
        'file' => ':attributeのファイルサイズは:maxキロバイト以下にしてください。',
        'numeric' => ':attributeは:max以下の値を指定してください。',
        'string' => ':attributeは:max文字以下で指定してください。',
    ],
    'max_digits' => ':attributeは:max桁以下で指定してください。',
    'mimes' => ':attributeには次のいずれかの種類のファイルを指定してください：:values。',
    'mimetypes' => ':attributeには次のいずれかの種類のファイルを指定してください：:values。',
    'min' => [
        'array' => ':attributeの項目数は:min個以上にしてください。',
        'file' => ':attributeのファイルサイズは:minキロバイト以上にしてください。',
        'numeric' => ':attributeは:min以上の値を指定してください。',
        'string' => ':attributeは:min文字以上で指定してください。',
    ],
    'min_digits' => ':attributeは:min桁以上で指定してください。',
    'missing' => ':attributeは指定しないでください。',
    'missing_if' => ':otherが:valueの場合、:attributeは指定しないでください。',
    'missing_unless' => ':otherが:valueでない限り、:attributeは指定しないでください。',
    'missing_with' => ':valuesが指定されている場合、:attributeは指定しないでください。',
    'missing_with_all' => ':valuesが全て指定されている場合、:attributeは指定しないでください。',
    'multiple_of' => ':attributeは:valueの倍数を指定してください。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が正しくありません。',
    'numeric' => ':attributeには数値を指定してください。',
    'password' => [
        'letters' => ':attributeには1文字以上の英字を含めてください。',
        'mixed' => ':attributeには英大文字・英小文字をそれぞれ1文字以上含めてください。',
        'numbers' => ':attributeには1文字以上の数字を含めてください。',
        'symbols' => ':attributeには1文字以上の記号を含めてください。',
        'uncompromised' => '指定された:attributeは過去の情報漏えいで確認されています。別の:attributeを指定してください。',
    ],
    'present' => ':attributeを指定してください。',
    'present_if' => ':otherが:valueの場合、:attributeを指定してください。',
    'present_unless' => ':otherが:valueでない限り、:attributeを指定してください。',
    'present_with' => ':valuesが指定されている場合、:attributeを指定してください。',
    'present_with_all' => ':valuesが全て指定されている場合、:attributeを指定してください。',
    'prohibited' => ':attributeは指定できません。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは指定できません。',
    'prohibited_if_accepted' => ':otherが承認されている場合、:attributeは指定できません。',
    'prohibited_if_declined' => ':otherが非承認の場合、:attributeは指定できません。',
    'prohibited_unless' => ':otherが:valuesのいずれかでない限り、:attributeは指定できません。',
    'prohibits' => ':attributeを指定すると、:otherは指定できません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeを入力してください。',
    'required_array_keys' => ':attributeには次のキーを含めてください：:values。',
    'required_if' => ':otherが:valueの場合、:attributeを入力してください。',
    'required_if_accepted' => ':otherが承認されている場合、:attributeを入力してください。',
    'required_if_declined' => ':otherが非承認の場合、:attributeを入力してください。',
    'required_unless' => ':otherが:valuesのいずれかでない限り、:attributeを入力してください。',
    'required_with' => ':valuesが指定されている場合、:attributeを入力してください。',
    'required_with_all' => ':valuesが全て指定されている場合、:attributeを入力してください。',
    'required_without' => ':valuesが指定されていない場合、:attributeを入力してください。',
    'required_without_all' => ':valuesがいずれも指定されていない場合、:attributeを入力してください。',
    'same' => ':attributeと:otherは同じ値を指定してください。',
    'size' => [
        'array' => ':attributeの項目数は:size個にしてください。',
        'file' => ':attributeのファイルサイズは:sizeキロバイトにしてください。',
        'numeric' => ':attributeは:sizeを指定してください。',
        'string' => ':attributeは:size文字で指定してください。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まるようにしてください：:values。',
    'string' => ':attributeには文字列を指定してください。',
    'timezone' => ':attributeには有効なタイムゾーンを指定してください。',
    'unique' => 'この:attributeは既に使用されています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字で指定してください。',
    'url' => ':attributeには有効なURLを指定してください。',
    'ulid' => ':attributeには有効なULIDを指定してください。',
    'uuid' => ':attributeには有効なUUIDを指定してください。',

    /*
    |--------------------------------------------------------------------------
    | 属性ごとのカスタムメッセージ
    |--------------------------------------------------------------------------
    |
    | 「attribute.rule」の形式で、特定の項目・ルールの組み合わせに対する
    | 専用メッセージを指定できる。
    |
    */

    'custom' => [
        'login_id' => [
            'regex' => 'ユーザーIDは半角英数字・アンダースコア（_）・ハイフン（-）・ピリオド（.）のみ使用できます。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 項目名（属性名）
    |--------------------------------------------------------------------------
    |
    | 「email」ではなく「メールアドレス」のように、エラーメッセージ中の
    | プレースホルダー（:attribute）をわかりやすい日本語名に置き換える。
    |
    */

    'attributes' => [
        'name' => '名前',
        'login_id' => 'ユーザーID',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認用）',
        'current_password' => '現在のパスワード',
        'token' => 'トークン',
        'role' => '権限',
        'permissions' => '個別権限',
        'permissions.*' => '個別権限',

        // 事務所の新規契約・自己登録
        'office_name' => '事務所名',
        'owner_name' => 'オーナー氏名',
        'owner_login_id' => 'オーナーのユーザーID',
        'owner_email' => 'オーナーのメールアドレス',
        'owner_password' => 'オーナーのパスワード',
        'owner_password_confirmation' => 'オーナーのパスワード（確認用）',

        // 運営管理画面（事務所・料金プラン・課金設定）
        'is_active' => '有効状態',
        'trial_ends_at' => 'トライアル終了日',
        'trial_days' => 'トライアル日数',
        'billing_cycle' => '請求サイクル',
        'enabled_modules' => '利用できる機能',
        'enabled_modules.*' => '利用できる機能',
        'billing_plan_id' => '料金プラン',
        'custom_monthly_price' => '月額の個別見積り・値引き',
        'max_clients' => '顧問先数の上限',
        'max_users' => 'ユーザー数の上限',
        'monthly_price' => '月額料金',
        'stripe_price_id' => 'Stripe価格ID',
        'sort_order' => '表示順',

        // 顧問先
        'search' => '検索キーワード',
        'status' => 'ステータス',
        'representative_name' => '代表者名',
        'address' => '住所',
        'phone' => '電話番号',
        'contract_start_date' => '契約開始日',
        'assigned_user_id' => '担当者',
        'notes' => '備考',

        // 手続きタスク
        'client_id' => '顧問先',
        'procedure_type_id' => '手続き種別',
        'procedure_type_ids' => '手続き種別',
        'procedure_type_ids.*' => '手続き種別',
        'title' => 'タイトル',
        'due_date' => '期限日',
        'due_from' => '期限日（開始）',
        'due_to' => '期限日（終了）',
        'return_to' => '戻り先',

        // 手続き種別マスタ
        'category' => 'カテゴリ',
        'recurrence_type' => '周期区分',
        'default_lead_days' => '通知タイミング（リード日数）',
        'default_lead_days.*' => '通知タイミング（リード日数）',
        'description' => '説明',

        // 進捗レポート
        'period_start' => '対象期間（開始）',
        'period_end' => '対象期間（終了）',
        'comment' => 'コメント',

        // カスタムフィールド
        'target' => '対象',
        'label' => '項目名',
        'field_type' => '項目の種類',
        'options' => '選択肢',
        'options.*' => '選択肢',

        // 書類チェックリスト
        'file' => 'ファイル',
        'is_required' => '必須項目',
        'retention_years' => '保存年数',
        'is_collected' => '収集済み',

        // Web Push購読
        'endpoint' => '通知エンドポイント',
        'public_key' => '公開鍵',
        'auth_token' => '認証トークン',
        'content_encoding' => '暗号化方式',

        // Excel移行アシスタント
        'mapping' => '項目の対応関係',
        'mapping.*' => '項目の対応関係',

        // 計算アシスタント
        'task_id' => 'タスク',
        'hire_date' => '入社日',
        'weekly_scheduled_days' => '週の所定労働日数',
        'type' => '種類',
        'input' => '入力内容',
        'result' => '計算結果',
        'months' => '月別実績',
        'months.*' => '月別実績',
        'months.*.month' => '対象月',
        'months.*.overtime_hours' => '時間外労働時間',
        'months.*.holiday_work_hours' => '休日労働時間',
        'shifts' => 'シフト',
        'shifts.*' => 'シフト',
        'shifts.*.date' => '日付',
        'shifts.*.hours' => '労働時間',

        // カレンダー
        'start' => '開始日時',
        'end' => '終了日時',

        // Excel/CSVエクスポート
        'format' => '出力形式',
    ],

];
