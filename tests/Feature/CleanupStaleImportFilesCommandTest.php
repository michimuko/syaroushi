<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

it('deletes import temp files older than 24 hours', function () {
    Storage::disk('local')->put('imports/1/old-token', 'dummy content');
    touch(Storage::disk('local')->path('imports/1/old-token'), now()->subHours(25)->getTimestamp());

    $this->artisan('imports:cleanup-stale-files')->assertExitCode(0);

    Storage::disk('local')->assertMissing('imports/1/old-token');
});

it('keeps import temp files uploaded within the last 24 hours', function () {
    Storage::disk('local')->put('imports/1/fresh-token', 'dummy content');
    touch(Storage::disk('local')->path('imports/1/fresh-token'), now()->subHours(1)->getTimestamp());

    $this->artisan('imports:cleanup-stale-files')->assertExitCode(0);

    Storage::disk('local')->assertExists('imports/1/fresh-token');
});

it('does nothing when there are no import temp files', function () {
    $this->artisan('imports:cleanup-stale-files')
        ->expectsOutputToContain('0件の一時ファイルを削除しました。')
        ->assertExitCode(0);
});
