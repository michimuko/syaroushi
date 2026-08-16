<?php

use App\Services\DesktopAppReleaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

function fakeGithubReleaseResponse(): array
{
    return [
        'tag_name' => 'desktop-app-v0.1.0',
        'published_at' => '2026-08-20T00:00:00Z',
        'assets' => [
            [
                'name' => 'syaroushi-desktop-notifier_0.1.0_x64-setup.exe',
                'size' => 12345,
                'url' => 'https://api.github.com/repos/michimuko/syaroushi/releases/assets/1',
            ],
            [
                'name' => 'syaroushi-desktop-notifier_0.1.0_x64_en-US.msi',
                'size' => 23456,
                'url' => 'https://api.github.com/repos/michimuko/syaroushi/releases/assets/2',
            ],
            [
                'name' => 'syaroushi-desktop-notifier_0.1.0_aarch64.dmg',
                'size' => 34567,
                'url' => 'https://api.github.com/repos/michimuko/syaroushi/releases/assets/3',
            ],
            [
                'name' => 'syaroushi-desktop-notifier_0.1.0_amd64.AppImage',
                'size' => 45678,
                'url' => 'https://api.github.com/repos/michimuko/syaroushi/releases/assets/4',
            ],
            [
                'name' => 'syaroushi-desktop-notifier_0.1.0_amd64.deb',
                'size' => 56789,
                'url' => 'https://api.github.com/repos/michimuko/syaroushi/releases/assets/5',
            ],
        ],
    ];
}

it('returns null without hitting the network when no token is configured', function () {
    config(['services.github.token' => null]);
    Http::fake();

    expect((new DesktopAppReleaseService)->latestRelease())->toBeNull();
    Http::assertNothingSent();
});

it('parses the latest release and categorizes assets by os', function () {
    config(['services.github.token' => 'test-token']);
    Http::fake([
        'api.github.com/*' => Http::response(fakeGithubReleaseResponse(), 200),
    ]);

    $release = (new DesktopAppReleaseService)->latestRelease();

    expect($release['version'])->toBe('desktop-app-v0.1.0')
        ->and($release['assets']['windows']['name'])->toBe('syaroushi-desktop-notifier_0.1.0_x64_en-US.msi')
        ->and($release['assets']['macos']['name'])->toBe('syaroushi-desktop-notifier_0.1.0_aarch64.dmg')
        ->and($release['assets']['linux']['name'])->toBe('syaroushi-desktop-notifier_0.1.0_amd64.AppImage');
});

it('caches the release so a second call does not hit github again', function () {
    config(['services.github.token' => 'test-token']);
    Http::fake([
        'api.github.com/*' => Http::response(fakeGithubReleaseResponse(), 200),
    ]);

    $service = new DesktopAppReleaseService;
    $service->latestRelease();
    $service->latestRelease();

    Http::assertSentCount(1);
});

it('returns null and does not cache when github responds with an error', function () {
    config(['services.github.token' => 'test-token']);
    Http::fake([
        'api.github.com/*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $service = new DesktopAppReleaseService;

    expect($service->latestRelease())->toBeNull();
    expect(Cache::get('desktop-app:latest-release'))->toBeNull();
});

it('exposes the matching asset via assetFor', function () {
    config(['services.github.token' => 'test-token']);
    Http::fake([
        'api.github.com/*' => Http::response(fakeGithubReleaseResponse(), 200),
    ]);

    $asset = (new DesktopAppReleaseService)->assetFor('windows');

    expect($asset['name'])->toBe('syaroushi-desktop-notifier_0.1.0_x64_en-US.msi')
        ->and($asset)->toHaveKey('api_url');
});
