<?php

/**
 * 特定個人情報を含み得る書類の保管先（企画書7.7章）が、
 * 非公開バケット＋サーバーサイド暗号化必須の設定を保っていることを保証する。
 * ローカル開発を含め、うっかりlocalディスクやSSEなし設定へ戻す変更を検知する。
 */
it('defaults to the s3 disk', function () {
    expect(config('filesystems.default'))->toBe('s3');
});

it('keeps the s3 disk private with server-side encryption enforced', function () {
    $disk = config('filesystems.disks.s3');

    expect($disk['visibility'])->toBe('private')
        ->and($disk['options']['ServerSideEncryption'])->toBe('AES256');
});
