<?php

namespace App\Http\Controllers\Concerns;

/**
 * 登録・編集直後に一覧へ戻る際、対象レコードが実際に表示されるページ番号を計算する。
 * 常にページ1へ戻すだけだと、一覧のソート順（名前順・期限順等）によっては新規/更新後の
 * レコードが別ページに埋もれてしまい、行ハイライト（Composables/useHighlightRow.js）が
 * 対象要素を見つけられず何も起きないため、レコードが含まれるページへ直接遷移させる。
 */
trait ComputesHighlightPage
{
    protected function pageContainingId($orderedQuery, int $id, int $perPage = 20): int
    {
        $position = $orderedQuery->pluck('id')->search($id);

        return $position === false ? 1 : intdiv($position, $perPage) + 1;
    }
}
