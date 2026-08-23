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

        <style>
            @media (prefers-reduced-motion: no-preference) {
                .reveal {
                    opacity: 0;
                    transform: translateY(20px);
                    transition: opacity .7s ease, transform .7s ease;
                }
                .reveal.is-visible {
                    opacity: 1;
                    transform: none;
                }
                .toast-card {
                    opacity: 0;
                    transform: translateX(24px);
                    transition: opacity .5s ease .15s, transform .5s ease .15s;
                }
                .toast-card.is-visible {
                    opacity: 1;
                    transform: none;
                }
                .float-card {
                    animation: lp-float 6s ease-in-out infinite;
                }
                @keyframes lp-float {
                    0%, 100% { transform: translateY(0) rotate(var(--r, 0deg)); }
                    50% { transform: translateY(-12px) rotate(var(--r, 0deg)); }
                }
            }
        </style>
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
                    <a href="#desktop-notification" class="hidden text-sm text-slate-600 hover:text-slate-900 sm:inline">デスクトップ通知</a>
                    <a href="#pricing" class="hidden text-sm text-slate-600 hover:text-slate-900 sm:inline">料金</a>
                    <a href="#security" class="hidden text-sm text-slate-600 hover:text-slate-900 sm:inline">セキュリティ</a>
                    <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-slate-900">ログイン</a>
                    <a href="{{ route('register') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition hover:-translate-y-0.5">
                        無料トライアルを始める
                    </a>
                </nav>
            </div>
        </header>

        {{-- Hero --}}
        <section class="relative overflow-hidden">
            {{-- 背景：スクリーンショットを重ねたコラージュ --}}
            <div class="pointer-events-none absolute inset-0 hidden xl:block" aria-hidden="true">
                <div
                    class="float-card absolute left-6 top-8 w-80 overflow-hidden rounded-xl border-4 border-white opacity-80 shadow-xl lg:left-16 lg:w-[26rem]"
                    style="--r:-5deg; transform: rotate(-5deg); aspect-ratio: 16 / 10; background-image:url('{{ asset('images/lp/screenshot-dashboard.png') }}'); background-size: 230% auto; background-position: 18% 14%;"
                ></div>
                <div
                    class="float-card absolute right-6 top-2 w-72 overflow-hidden rounded-xl border-4 border-white opacity-80 shadow-xl lg:right-16 lg:w-96"
                    style="--r:4deg; transform: rotate(4deg); animation-delay: .4s; aspect-ratio: 16 / 11; background-image:url('{{ asset('images/lp/screenshot-clients.png') }}'); background-size: 260% auto; background-position: 50% 30%;"
                ></div>
                <div
                    class="float-card absolute bottom-4 left-10 w-64 overflow-hidden rounded-xl border-4 border-white opacity-80 shadow-xl lg:left-28 lg:w-80"
                    style="--r:-4deg; transform: rotate(-4deg); animation-delay: .8s; aspect-ratio: 16 / 12; background-image:url('{{ asset('images/lp/screenshot-calendar.png') }}'); background-size: 250% auto; background-position: 50% 58%;"
                ></div>
                <div
                    class="float-card absolute bottom-0 right-10 w-72 overflow-hidden rounded-xl border-4 border-white opacity-80 shadow-xl lg:right-24 lg:w-96"
                    style="--r:5deg; transform: rotate(5deg); animation-delay: .2s; aspect-ratio: 16 / 11; background-image:url('{{ asset('images/lp/screenshot-dashboard.png') }}'); background-size: 260% auto; background-position: 82% 62%;"
                ></div>
                {{-- 中央のテキストを読みやすくするための白いビネット --}}
                <div class="absolute inset-0" style="background: radial-gradient(ellipse 60% 55% at 50% 40%, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.85) 45%, rgba(255,255,255,0) 75%);"></div>
            </div>

            <div class="relative mx-auto max-w-6xl px-6 pb-20 pt-16 sm:pt-24">
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
                        <a href="{{ route('register') }}" class="w-full rounded-md bg-indigo-600 px-8 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:w-auto transition hover:-translate-y-0.5">
                            {{ $trialDays }}日間無料トライアルを始める
                        </a>
                        <a href="#features" class="w-full rounded-md border border-slate-300 bg-white px-8 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto transition hover:-translate-y-0.5">
                            機能を見る
                        </a>
                    </div>
                    <p class="mt-4 text-xs text-slate-400">クレジットカード登録不要。すぐにお試しいただけます。</p>
                </div>

                {{-- モバイル：シンプルな単一スクリーンショット --}}
                <div class="mx-auto mt-16 max-w-4xl overflow-hidden rounded-xl border border-slate-200 shadow-xl shadow-slate-200/50 xl:hidden">
                    <div class="flex items-center gap-1.5 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    </div>
                    <img src="{{ asset('images/lp/screenshot-dashboard.png') }}" alt="ダッシュボード画面：期限超過・直近7日・30日以内の件数、担当者別ワークロード、手続き種別ごとの内訳を一覧表示" class="w-full" width="1440" height="900" loading="eager">
                </div>
            </div>
        </section>

        {{-- 課題提起 --}}
        <section class="relative overflow-hidden border-t border-slate-100 bg-slate-50">
            <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-indigo-100 opacity-60 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-sky-100 opacity-60 blur-3xl" aria-hidden="true"></div>

            <div class="relative mx-auto max-w-6xl px-6 py-16">
                <h2 class="reveal text-center text-2xl font-bold text-slate-900 sm:text-3xl">
                    こんな課題、抱えていませんか？
                </h2>
                <div class="mt-12 grid gap-8 sm:grid-cols-3">
                    @foreach ([
                        [
                            'title' => 'Excelの期限管理表が属人化',
                            'body' => '担当者ごとに管理方法がバラバラで、休みや退職のたびに引き継ぎが大変。',
                            'badge' => 'bg-indigo-50 text-indigo-600',
                            'icon' => '<rect x="3" y="8" width="11" height="13" rx="1.5"/><rect x="10" y="4" width="11" height="13" rx="1.5"/><line x1="13" y1="8" x2="18" y2="8"/><line x1="13" y1="11" x2="18" y2="11"/>',
                        ],
                        [
                            'title' => '期限超過に気づくのが遅い',
                            'body' => '顧問先が増えるほど、手続きの抜け漏れに気づいた時には期限直前ということも。',
                            'badge' => 'bg-red-50 text-red-500',
                            'icon' => '<circle cx="12" cy="12" r="8.25"/><path d="M12 7.5V12l3 2"/>',
                        ],
                        [
                            'title' => '進捗を顧問先に説明する手間',
                            'body' => '「今どの手続きがどこまで進んでいるか」を都度資料にまとめて説明するのが負担。',
                            'badge' => 'bg-sky-50 text-sky-600',
                            'icon' => '<rect x="3" y="5" width="18" height="11" rx="2"/><path d="M8 16l-2 3.5V16"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="7" y1="12.5" x2="14" y2="12.5"/>',
                        ],
                    ] as $pain)
                        <div class="reveal rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100" style="transition-delay: {{ $loop->index * 0.1 }}s;">
                            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full {{ $pain['badge'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true">{!! $pain['icon'] !!}</svg>
                            </div>
                            <h3 class="font-semibold text-slate-900">{{ $pain['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $pain['body'] }}</p>
                        </div>
                    @endforeach
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
            {{-- 顧問先・手続きタスク管理 --}}
            <div class="reveal mt-14 grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                <div class="overflow-hidden rounded-xl border border-slate-200 shadow-lg shadow-slate-200/50">
                    <div class="flex items-center gap-1.5 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    </div>
                    <img src="{{ asset('images/lp/screenshot-clients.png') }}" alt="顧問先一覧画面：顧問先名・代表者・電話番号・担当者・契約ステータスを一覧表示" class="w-full" width="1440" height="900" loading="lazy">
                </div>
                <div>
                    <h3 class="text-xl font-bold text-slate-900 sm:text-2xl">顧問先・手続きタスク管理</h3>
                    <p class="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                        顧問先ごとに手続きを紐付け、進捗ステータスを一覧で管理できます。担当者・ステータス・手続き種別で絞り込みができ、Excelでの一括登録・出力にも対応しています。
                    </p>
                </div>
            </div>

            {{-- カレンダー表示 --}}
            <div class="reveal mt-14 grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                <div class="order-2 lg:order-1">
                    <h3 class="text-xl font-bold text-slate-900 sm:text-2xl">カレンダーで期限を俯瞰</h3>
                    <p class="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                        手続きの期限をカレンダー表示で俯瞰し、日ごとの対応件数をひと目で把握できます。ステータスごとに色分けされるため、期限超過（赤）を見逃しません。
                    </p>
                </div>
                <div class="order-1 overflow-hidden rounded-xl border border-slate-200 shadow-lg shadow-slate-200/50 lg:order-2">
                    <div class="flex items-center gap-1.5 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    </div>
                    <img src="{{ asset('images/lp/screenshot-calendar.png') }}" alt="カレンダー画面：手続きの期限をステータス別に色分けして月表示" class="w-full" width="1440" height="900" loading="lazy">
                </div>
            </div>

            {{-- 自動アラート・ダッシュボード --}}
            <div class="mt-14 grid gap-6 sm:grid-cols-2">
                @foreach ([
                    [
                        'title' => '自動生成・自動アラート',
                        'body' => '定型手続きのタスクを自動生成し、期限前にメールでリマインドします。',
                        'badge' => 'bg-amber-50 text-amber-600',
                        'icon' => '<path d="M7 16l1-2c.5-.7.8-1.6.8-2.5V9a3.2 3.2 0 016.4 0v2.5c0 .9.3 1.8.8 2.5l1 2H7z"/><path d="M10 18.5a2 2 0 004 0"/>',
                    ],
                    [
                        'title' => '担当者別ダッシュボード',
                        'body' => '期限超過・直近7日・30日以内の件数を担当者別に一目で確認できます（上部の画面例を参照）。',
                        'badge' => 'bg-emerald-50 text-emerald-600',
                        'icon' => '<rect x="4" y="12" width="3.5" height="8" rx="0.5"/><rect x="10.25" y="7" width="3.5" height="13" rx="0.5"/><rect x="16.5" y="3.5" width="3.5" height="16.5" rx="0.5"/>',
                    ],
                ] as $feature)
                    <div class="rounded-xl border border-slate-200 p-6">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full {{ $feature['badge'] }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true">{!! $feature['icon'] !!}</svg>
                        </div>
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
                    [
                        'title' => 'Excel移行アシスタント',
                        'bg' => 'bg-emerald-50',
                        'badge' => 'bg-emerald-100 text-emerald-600',
                        'text' => 'text-emerald-800',
                        'icon' => '<path d="M12 4v10"/><path d="M8.5 11l3.5 3.5L15.5 11"/><path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>',
                    ],
                    [
                        'title' => '顧問先向け進捗レポートPDF自動生成',
                        'bg' => 'bg-sky-50',
                        'badge' => 'bg-sky-100 text-sky-600',
                        'text' => 'text-sky-800',
                        'icon' => '<rect x="5" y="3" width="14" height="18" rx="1.5"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/>',
                    ],
                    [
                        'title' => 'Web Push通知',
                        'bg' => 'bg-amber-50',
                        'badge' => 'bg-amber-100 text-amber-600',
                        'text' => 'text-amber-800',
                        'icon' => '<path d="M7 16l1-2c.5-.7.8-1.6.8-2.5V9a3.2 3.2 0 016.4 0v2.5c0 .9.3 1.8.8 2.5l1 2H7z"/><path d="M10 18.5a2 2 0 004 0"/>',
                    ],
                    [
                        'title' => '計算アシスタント（有休付与・36協定上限等）',
                        'bg' => 'bg-violet-50',
                        'badge' => 'bg-violet-100 text-violet-600',
                        'text' => 'text-violet-800',
                        'icon' => '<rect x="5" y="3" width="14" height="18" rx="2"/><rect x="7.5" y="5.5" width="9" height="4" rx="0.5"/><circle cx="8.5" cy="13" r="1"/><circle cx="12" cy="13" r="1"/><circle cx="15.5" cy="13" r="1"/><circle cx="8.5" cy="17" r="1"/><circle cx="12" cy="17" r="1"/><circle cx="15.5" cy="17" r="1"/>',
                    ],
                    [
                        'title' => 'カスタムフィールド',
                        'bg' => 'bg-rose-50',
                        'badge' => 'bg-rose-100 text-rose-600',
                        'text' => 'text-rose-800',
                        'icon' => '<line x1="4" y1="6" x2="20" y2="6"/><circle cx="9" cy="6" r="1.8"/><line x1="4" y1="12" x2="20" y2="12"/><circle cx="15" cy="12" r="1.8"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="11" cy="18" r="1.8"/>',
                    ],
                ] as $module)
                    <div class="rounded-xl {{ $module['bg'] }} p-5 text-center">
                        <div class="mx-auto mb-3 inline-flex h-11 w-11 items-center justify-center rounded-full {{ $module['badge'] }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5" aria-hidden="true">{!! $module['icon'] !!}</svg>
                        </div>
                        <p class="text-sm font-medium {{ $module['text'] }}">{{ $module['title'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- デスクトップ通知 --}}
        <section id="desktop-notification" class="relative overflow-hidden border-t border-slate-100 bg-slate-50">
            <div class="pointer-events-none absolute -right-24 top-1/2 h-80 w-80 -translate-y-1/2 rounded-full bg-amber-100 opacity-50 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-indigo-100 opacity-50 blur-3xl" aria-hidden="true"></div>

            <div class="relative mx-auto max-w-6xl px-6 py-20">
                <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div class="reveal">
                        <p class="mb-4 inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                            Windows・Mac・Linux対応
                        </p>
                        <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                            デスクトップ通知で、確認漏れをゼロに
                        </h2>
                        <p class="mt-4 text-sm leading-relaxed text-slate-600 sm:text-base">
                            常駐トレイアプリが期限の近いタスクをバックグラウンドで確認し、OS標準の通知でお知らせします。
                            メールやWebブラウザを開いていなくても、パソコンの画面上で気づけます。
                        </p>
                        <ul class="mt-6 space-y-3 text-sm text-slate-600 sm:text-base">
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-5 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4l2.3 2.29 6.3-6.29a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                <span>タスクトレイに常駐し、パソコン起動時に自動で立ち上がる設定も可能</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-5 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4l2.3 2.29 6.3-6.29a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                <span>確認間隔は自由に調整。メール・Web Pushと重複して何度も通知しない設計</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="mt-0.5 h-5 w-5 flex-none text-indigo-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4l2.3 2.29 6.3-6.29a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                <span>アクセストークンはいつでも設定画面から失効可能</span>
                            </li>
                        </ul>
                    </div>

                    <div class="relative h-72 sm:h-80" aria-hidden="true">
                        <div class="toast-card absolute right-4 top-6 flex w-72 items-start gap-3 rounded-xl border border-slate-100 bg-white p-4 opacity-90 shadow-lg sm:w-80" style="transition-delay: 0s;">
                            <svg viewBox="0 0 24 24" class="h-8 w-8 flex-none text-indigo-400" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M7 2a1 1 0 011 1v1h8V3a1 1 0 112 0v1h1a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h1V3a1 1 0 011-1zM4 9v11a1 1 0 001 1h14a1 1 0 001-1V9H4z"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M8 13.2l2.4 2.4L16 10.4"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">株式会社みらい工業</p>
                                <p class="mt-0.5 text-xs leading-relaxed text-slate-500">賞与支払届の期限が近づいています</p>
                            </div>
                        </div>
                        <div class="toast-card absolute right-8 top-24 flex w-72 items-start gap-3 rounded-xl border border-slate-100 bg-white p-4 shadow-2xl sm:right-12 sm:w-80" style="transition-delay: .2s;">
                            <svg viewBox="0 0 24 24" class="h-8 w-8 flex-none text-indigo-600" xmlns="http://www.w3.org/2000/svg">
                                <path fill="currentColor" d="M7 2a1 1 0 011 1v1h8V3a1 1 0 112 0v1h1a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2h1V3a1 1 0 011-1zM4 9v11a1 1 0 001 1h14a1 1 0 001-1V9H4z"/>
                                <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M8 13.2l2.4 2.4L16 10.4"/>
                            </svg>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-slate-900">{{ config('app.name') }}</p>
                                    <span class="text-[11px] text-slate-400">たった今</span>
                                </div>
                                <p class="mt-0.5 text-xs leading-relaxed text-slate-600">さくら商事株式会社の「36協定届」が明日期限です</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 料金 --}}
        <section id="pricing" class="mx-auto max-w-6xl px-6 py-20">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="reveal text-2xl font-bold text-slate-900 sm:text-3xl">シンプルな料金プラン</h2>
                <p class="reveal mt-3 text-sm text-slate-600 sm:text-base">
                    どのプランも{{ $trialDays }}日間無料トライアル付き、クレジットカード登録は不要です。
                </p>
            </div>
            <div class="mx-auto mt-12 grid max-w-5xl gap-6 lg:grid-cols-4">
                @foreach ($billingPlans as $plan)
                    @php $isRecommended = $plan->name === 'スタンダード'; @endphp
                    <div
                        class="reveal relative flex flex-col rounded-xl border p-6 {{ $isRecommended ? 'border-indigo-600 shadow-lg' : 'border-slate-200' }}"
                        style="transition-delay: {{ $loop->index * 0.1 }}s;"
                    >
                        @if ($isRecommended)
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-600 px-3 py-1 text-xs font-semibold text-white">
                                おすすめ
                            </span>
                        @endif
                        <h3 class="text-lg font-bold text-slate-900">{{ $plan->name }}</h3>
                        <p class="mt-4">
                            @if ($plan->monthly_price !== null)
                                <span class="text-3xl font-bold text-slate-900">¥{{ number_format($plan->monthly_price) }}</span>
                                <span class="text-sm text-slate-500">/月</span>
                            @else
                                <span class="text-2xl font-bold text-slate-900">個別見積り</span>
                            @endif
                        </p>
                        <ul class="mt-6 space-y-2 text-sm text-slate-600">
                            <li>顧問先数：{{ $plan->max_clients !== null ? $plan->max_clients.'件まで' : '無制限' }}</li>
                            <li>ユーザー数：{{ $plan->max_users !== null ? $plan->max_users.'人まで' : '無制限' }}</li>
                        </ul>
                        <div class="mt-6">
                            @if ($plan->monthly_price !== null)
                                <a href="{{ route('register') }}" class="block w-full rounded-md px-4 py-2.5 text-center text-sm font-semibold transition hover:-translate-y-0.5 {{ $isRecommended ? 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-700' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                                    無料トライアルを始める
                                </a>
                            @else
                                <a href="mailto:{{ $contactEmail }}?subject={{ urlencode('エンタープライズプランについての問い合わせ') }}" class="block w-full rounded-md border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-50">
                                    問い合わせる
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="reveal mx-auto mt-8 max-w-2xl text-center text-xs text-slate-400">
                プラン上限を超えても登録がブロックされることはありません（上限超過時は画面上に案内が表示されます）。
            </p>
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
                    @foreach ([
                        [
                            'title' => '事務所ごとのデータ分離',
                            'body' => '事務所（テナント）単位でデータを分離し、他事務所のデータへアクセスできない設計にしています。',
                            'badge' => 'bg-indigo-50 text-indigo-600',
                            'icon' => '<rect x="6" y="11" width="12" height="9" rx="1.5"/><path d="M8.5 11V8a3.5 3.5 0 017 0v3"/><circle cx="12" cy="15" r="1.1"/>',
                        ],
                        [
                            'title' => '書類のアクセスログ・保存期限管理',
                            'body' => 'アップロードされた書類は署名付きURLで配信し、誰がいつアクセスしたかを記録します。',
                            'badge' => 'bg-sky-50 text-sky-600',
                            'icon' => '<rect x="5" y="3" width="11" height="15" rx="1.5"/><line x1="7.5" y1="7" x2="13.5" y2="7"/><line x1="7.5" y1="10.5" x2="13.5" y2="10.5"/><circle cx="16" cy="16" r="3"/><line x1="18.1" y1="18.1" x2="20.5" y2="20.5"/>',
                        ],
                        [
                            'title' => 'マイナンバー入力の検知警告',
                            'body' => '自由入力欄にマイナンバーとみられる値が入力された場合に警告を表示します。',
                            'badge' => 'bg-amber-50 text-amber-600',
                            'icon' => '<path d="M12 4L21 19H3L12 4z" stroke-linejoin="round"/><line x1="12" y1="10.5" x2="12" y2="14"/><circle cx="12" cy="16.5" r="0.9" fill="currentColor" stroke="none"/>',
                        ],
                        [
                            'title' => 'きめ細かな権限管理',
                            'body' => '事務所オーナーが、手続き種別マスタ編集などの操作をスタッフへ個別に委譲できます。',
                            'badge' => 'bg-emerald-50 text-emerald-600',
                            'icon' => '<circle cx="7.5" cy="12" r="3.25"/><line x1="10.5" y1="12" x2="19" y2="12"/><line x1="15.5" y1="12" x2="15.5" y2="15"/><line x1="18" y1="12" x2="18" y2="15"/>',
                        ],
                    ] as $item)
                        <div class="reveal rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100" style="transition-delay: {{ $loop->index * 0.1 }}s;">
                            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full {{ $item['badge'] }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true">{!! $item['icon'] !!}</svg>
                            </div>
                            <h3 class="font-semibold text-slate-900">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 資料請求・マニュアル --}}
        @php
            $materialsSubject = '【資料請求】'.config('app.name');
            $materialsBody = "資料請求を希望します。\n"
                ."\n"
                ."【事務所名】\n"
                ."\n"
                ."【ご担当者名】\n"
                ."\n"
                ."【その他ご要望があればご記載ください】\n"
                ."\n";
            $materialsMailto = 'mailto:'.$contactEmail.'?subject='.urlencode($materialsSubject).'&body='.urlencode($materialsBody);
        @endphp
        <section class="border-t border-slate-100 bg-slate-50">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="reveal text-2xl font-bold text-slate-900 sm:text-3xl">資料でじっくりご検討いただけます</h2>
                    <p class="reveal mt-3 text-sm text-slate-600 sm:text-base">
                        導入のご検討に役立つ資料をご用意しています。お気軽にご請求ください。
                    </p>
                </div>
                <div class="mx-auto mt-12 grid max-w-3xl gap-6 sm:grid-cols-2">
                    <div class="reveal flex flex-col rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6"/></svg>
                        </div>
                        <h3 class="font-semibold text-slate-900">サービス資料を請求する</h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">
                            事務所名・ご要望を記入するだけのメールをその場で作成できます。担当より資料をお送りします。
                        </p>
                        <a href="{{ $materialsMailto }}" class="mt-6 block w-full rounded-md bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-700">
                            資料請求メールを作成する
                        </a>
                    </div>
                    <div class="reveal flex flex-col rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-100" style="transition-delay: .1s;">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 text-sky-600">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="1.5"/><line x1="8" y1="8" x2="16" y2="8"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="8" y1="16" x2="13" y2="16"/></svg>
                        </div>
                        <h3 class="font-semibold text-slate-900">操作マニュアルを見る</h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">
                            実際の画面操作やボタンの動作までまとめた詳細マニュアルをPDFでご覧いただけます。
                        </p>
                        <a href="{{ route('manual.download') }}" class="mt-6 block w-full rounded-md border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-50">
                            操作マニュアルをダウンロード（PDF）
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- 最終CTA --}}
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="reveal relative overflow-hidden rounded-2xl bg-indigo-600 px-8 py-14 text-center sm:px-16">
                <div class="pointer-events-none absolute -left-10 -top-16 h-56 w-56 rounded-full bg-white opacity-10 blur-2xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-16 -right-10 h-56 w-56 rounded-full bg-white opacity-10 blur-2xl" aria-hidden="true"></div>
                <div class="relative">
                    <h2 class="text-2xl font-bold text-white sm:text-3xl">まずは無料トライアルでお試しください</h2>
                    <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-indigo-100 sm:text-base">
                        クレジットカード登録は不要です。{{ $trialDays }}日間、実際の画面で機能をご確認いただけます。
                        エンタープライズプランや導入相談は、お気軽にお問い合わせください。
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="w-full rounded-md bg-white px-8 py-3 text-center text-sm font-semibold text-indigo-700 shadow-sm hover:bg-indigo-50 sm:w-auto transition hover:-translate-y-0.5">
                            無料トライアルを始める
                        </a>
                        <a href="mailto:{{ $contactEmail }}?subject={{ urlencode('導入についての問い合わせ') }}" class="w-full rounded-md border border-indigo-300 px-8 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-500 sm:w-auto transition hover:-translate-y-0.5">
                            導入について問い合わせる
                        </a>
                    </div>
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

        <script>
            (function () {
                var targets = document.querySelectorAll('.reveal, .toast-card');
                if (!('IntersectionObserver' in window)) {
                    targets.forEach(function (el) { el.classList.add('is-visible'); });
                    return;
                }
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });
                targets.forEach(function (el) { io.observe(el); });
            })();
        </script>
    </body>
</html>
