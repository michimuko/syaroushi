<template>
    <div
        class="flex min-h-screen flex-col items-center bg-slate-900 pt-10 sm:justify-center sm:pt-0"
    >
        <div class="flex flex-col items-center gap-2">
            <span
                class="rounded-md bg-slate-800 px-3 py-1 text-xs font-semibold tracking-wide text-slate-300"
            >
                運営管理
            </span>
            <span class="flex flex-col items-center">
                <span class="text-lg font-semibold text-white">
                    キゲンバン
                </span>
                <span class="text-xs text-slate-400">
                    社労士業務進捗管理
                </span>
            </span>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
