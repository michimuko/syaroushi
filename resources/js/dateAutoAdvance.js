// input[type="date"]は年→月→日のセグメント間移動をブラウザが標準で行うため、
// ここでは「日まで入力し終えて日付が確定した際に、次のフォーム項目へフォーカスを移す」
// 部分だけを補う。個々の画面ごとに実装すると付け忘れが起きるため、
// documentレベルのイベント委譲で全date入力に自動適用する（app.jsで一度だけ初期化）。
const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

const wasCompleteByElement = new WeakMap();

function isVisible(element) {
    return element.offsetParent !== null;
}

function focusNextField(current) {
    const focusable = Array.from(
        document.querySelectorAll(FOCUSABLE_SELECTOR),
    ).filter(isVisible);
    const index = focusable.indexOf(current);
    if (index === -1) return;

    const next = focusable[index + 1];
    if (next) next.focus();
}

function handleInput(event) {
    const target = event.target;
    if (!(target instanceof HTMLInputElement) || target.type !== 'date') {
        return;
    }

    // input[type="date"]は年月日すべてが揃って初めてvalueが"yyyy-mm-dd"（10文字）になる。
    const isComplete = target.value.length === 10;
    const wasComplete = wasCompleteByElement.get(target) === true;
    wasCompleteByElement.set(target, isComplete);

    if (isComplete && !wasComplete) {
        focusNextField(target);
    }
}

export function initDateInputAutoAdvance() {
    document.addEventListener('input', handleInput, true);
}
