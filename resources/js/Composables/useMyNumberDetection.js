import { computed } from 'vue';

// マイナンバー（個人番号）は12桁の数字。ハイフン区切り（例: 1234-5678-9012）も許容して検知する。
const MY_NUMBER_PATTERN = /(?<!\d)\d{4}-?\d{4}-?\d{4}(?!\d)/;

export function containsMyNumberLikeString(value) {
    if (!value) {
        return false;
    }

    return MY_NUMBER_PATTERN.test(value);
}

export function useMyNumberDetection(textRef) {
    return computed(() => containsMyNumberLikeString(textRef.value));
}
