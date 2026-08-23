import { usePage } from '@inertiajs/vue3';
import { nextTick, watch } from 'vue';

/**
 * 登録・編集直後に一覧へ戻ったとき、対象行(id="row-{id}")までスクロールして
 * 一瞬ハイライトする。対象行が現在のページ・フィルタ条件に含まれない場合は何もしない。
 * ハイライト対象のIDはコントローラー側の redirect()->with('highlightId', $model->id) で
 * セッションフラッシュされ、HandleInertiaRequests経由でflash.highlightIdとして共有される。
 *
 * onMountedではなくwatch(immediate: true)を使う。カスタムフィールド設定・料金プラン等、
 * 追加/編集フォームが一覧と同じページ内にありback()で同じVueコンポーネントに留まる画面では、
 * Inertiaが同一コンポーネントを再マウントせずpropsだけ更新するためonMountedが発火しない。
 */
export function useHighlightRow() {
    const page = usePage();

    watch(
        () => page.props.flash?.highlightId,
        async (id) => {
            if (!id) return;

            await nextTick();

            const el = document.getElementById(`row-${id}`);
            if (!el) return;

            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el.classList.add('row-highlight');
            el.addEventListener(
                'animationend',
                () => el.classList.remove('row-highlight'),
                { once: true },
            );
        },
        { immediate: true },
    );
}
