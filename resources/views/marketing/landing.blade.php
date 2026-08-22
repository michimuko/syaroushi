<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}｜顧問先の法定手続き期限管理SaaS</title>
        <meta name="description" content="算定基礎届・労働保険年度更新・36協定届など、顧問先ごとの法定手続きの進捗と期限を一元管理。自動アラートで期限漏れを防ぐ、社労士事務所向け業務進捗・期限管理SaaSです。">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ config('app.name') }}｜顧問先の法定手続き期限管理SaaS">
        <meta property="og:description" content="顧問先ごとの法定手続きの進捗と期限を一元管理し、自動アラートで期限漏れを防ぐ社労士事務所向けSaaS。">
        <meta name="robots" content="index,follow">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

        @vite('resources/css/app.css')
    </head>
    <body class="scroll-smooth bg-white font-sans text-slate-800 antialiased">

        {{-- Header --}}
        <header class="sticky top-0 z-20 border-b border-slate-100 bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <svg viewBox="0 0 24 24" class="h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg">
                        <path fill="currentColor" d="M7 2a1 1 0 011 1v1h8V3a1 1 0 112 0v1h1a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h1V3a1 1 0 011-1zM4 9v11a1 1 0 001 1h14a1 1 0 001-1V9H4z"/>
                        <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M8 13.2l2.4 2.4L16 10.4"/>
                    </svg>
                    <span class="text-lg font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                </div>
                <nav class="flex items-center gap-6">
                    <a href="#features" class="hidden text-sm text-slate-600 hover:text-slate-900 sm:inline">機能</a>
                    <a href="#security" class="hidden text-sm text-slate-600 hover:text-slate-900 sm:inline">セキュリティ</a>
                    <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-slate-900">ログイン</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        無料トライアルを始める
                    </a>
                </nav>
            </div>
        </header>

        {{-- Hero --}}
        <section class="mx-auto max-w-6xl px-6 pb-20 pt-16 sm:pt-24">
            <div class="mx-auto max-w-3xl text-center">
                <p class="mb-4 inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    社労士事務所向け 業務進捗・期限管理SaaS
                </p>
                <h1 class="text-3xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl">
                    顧問先の法定手続き、<br class="hidden sm:block">期限漏れゼロへ。
                </h1>
                <p class="mx-auto mt-6 max-w-xl text-base leading-relaxed text-slate-600 sm:text-lg">
                    算定基礎届・労働保険年度更新・36協定届など、顧問先ごとの手続き進捗と期限を一元管理。
                    自動アラートと担当者別ダッシュボードで、Excel台帳や属人化した管理から卒業できます。
                </p>
                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="w-full rounded-md bg-indigo-600 px-8 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:w-auto">
                        {{ $trialDays }}日間無料トライアルを始める
                    </a>
                    <a href="#features" class="w-full rounded-md border border-slate-300 px-8 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">
                        機能を見る
                    </a>
                </div>
                <p class="mt-4 text-xs text-slate-400">クレジットカード登録不要。すぐにお試しいただけます。</p>
            </div>
        </section>

        {{-- 課題提起 --}}
        <section class="border-t border-slate-100 bg-slate-50">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <h2 class="text-center text-2xl font-bold text-slate-900 sm:text-3xl">
                    こんな課題、抱えていませんか？
                </h2>
                <div class="mt-12 grid gap-8 sm:grid-cols-3">
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="font-semibold text-slate-900">Excelの期限管理表が属人化</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            担当者ごとに管理方法がバラバラで、休みや退職のたびに引き継ぎが大変。
                        </p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="font-semibold text-slate-900">期限超過に気づくのが遅い</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            顧問先が増えるほど、手続きの抜け漏れに気づいた時には期限直前ということも。
                        </p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="font-semibold text-slate-900">進捗を顧問先に説明する手間</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            「今どの手続きがどこまで進んでいるか」を都度資料にまとめて説明するのが負担。
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 基本機能 --}}
        <section id="features" class="mx-auto max-w-6xl px-6 py-20">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">基本機能</h2>
                <p class="mt-3 text-sm text-slate-600 sm:text-base">
                    顧問先・手続きの管理に必要な機能を、追加費用なしの基本機能として提供します。
                </p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['title' => '顧問先・手続きタスク管理', 'body' => '顧問先ごとに手続きを紐付け、進捗ステータスを一覧で管理できます。'],
                    ['title' => 'カレンダー表示', 'body' => '手続きの期限をカレンダーで俯瞰し、日ごとの対応件数を把握できます。'],
                    ['title' => '自動生成・自動アラート', 'body' => '定型手続きのタスクを自動生成し、期限前にメールでリマインドします。'],
                    ['title' => '担当者別ダッシュボード', 'body' => '期限超過・直近7日・30日以内の件数を担当者別に一目で確認できます。'],
                ] as $feature)
                    <div class="rounded-xl border border-slate-200 p-6">
                        <h3 class="font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mx-auto mt-16 max-w-2xl text-center">
                <p class="inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    拡張モジュール（事務所ごとに必要な分だけON）
                </p>
                <p class="mt-3 text-sm text-slate-600 sm:text-base">
                    使わない機能で画面を複雑にしないよう、以下はオプションとして個別に有効化できます。
                </p>
            </div>
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    'Excel移行アシスタント',
                    '顧問先向け進捗レポートPDF自動生成',
                    'Web Push通知',
                    '計算アシスタント（有休付与・36協定上限等）',
                    'カスタムフィールド',
                ] as $module)
                    <div class="rounded-xl bg-slate-50 p-5 text-center text-sm font-medium text-slate-700">
                        {{ $module }}
                    </div>
                @endforeach
            </div>
        </section>

        {{-- セキュリティ --}}
        <section id="security" class="border-t border-slate-100 bg-slate-50">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">個人情報を預かる前提の設計</h2>
                    <p class="mt-3 text-sm text-slate-600 sm:text-base">
                        顧問先の個人情報・マイナンバー関連情報を扱うサービスとして、セキュリティを優先して設計しています。
                    </p>
                </div>
                <div class="mt-12 grid gap-8 sm:grid-cols-2">
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="font-semibold text-slate-900">事務所ごとのデータ分離</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            事務所（テナント）単位でデータを分離し、他事務所のデータへアクセスできない設計にしています。
                        </p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="font-semibold text-slate-900">書類のアクセスログ・保存期限管理</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            アップロードされた書類は署名付きURLで配信し、誰がいつアクセスしたかを記録します。
                        </p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="font-semibold text-slate-900">マイナンバー入力の検知警告</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            自由入力欄にマイナンバーとみられる値が入力された場合に警告を表示します。
                        </p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="font-semibold text-slate-900">きめ細かな権限管理</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">
                            事務所オーナーが、手続き種別マスタ編集などの操作をスタッフへ個別に委譲できます。
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 最終CTA --}}
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="rounded-2xl bg-indigo-600 px-8 py-14 text-center sm:px-16">
                <h2 class="text-2xl font-bold text-white sm:text-3xl">まずは無料トライアルでお試しください</h2>
                <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-indigo-100 sm:text-base">
                    クレジットカード登録は不要です。{{ $trialDays }}日間、実際の画面で機能をご確認いただけます。
                    料金プランについては、お問い合わせください。
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="w-full rounded-md bg-white px-8 py-3 text-center text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50 sm:w-auto">
                        無料トライアルを始める
                    </a>
                    <a href="mailto:{{ $contactEmail }}?subject={{ urlencode('料金プランについての問い合わせ') }}" class="w-full rounded-md border border-indigo-300 px-8 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500 sm:w-auto">
                        料金について問い合わせる
                    </a>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="border-t border-slate-100">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 py-8 text-xs text-slate-400 sm:flex-row">
                <span>&copy; {{ now()->year }} {{ config('app.name') }}</span>
                <a href="mailto:{{ $contactEmail }}" class="hover:text-slate-600">{{ $contactEmail }}</a>
            </div>
        </footer>
    </body>
</html>
