<?php

use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

function fakeGithubDownloadRelease(): array
{
    return [
        'tag_name' => 'desktop-app-v0.1.0',
        'published_at' => '2026-08-20T00:00:00Z',
        'assets' => [
            [
                'name' => 'syaroushi-desktop-notifier_0.1.0_x64_en-US.msi',
                'size' => 23456,
                'url' => 'https://api.github.com/repos/michimuko/syaroushi/releases/assets/2',
            ],
        ],
    ];
}

it('requires authentication', function () {
    $this->get(route('settings.desktop-app.download', 'windows'))
        ->assertRedirect(route('login'));
});

it('returns 404 for an unsupported os', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $this->actingAs($user)
        ->get(route('settings.desktop-app.download', 'android'))
        ->assertNotFound();
});

it('returns 404 when no installer is available for the requested os', function () {
    config(['services.github.token' => 'test-token']);
    Http::fake([
        'api.github.com/*' => Http::response(fakeGithubDownloadRelease(), 200),
    ]);

    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    // macosの資産が存在しないfakeレスポンスに対してmacosを要求する
    $this->actingAs($user)
        ->get(route('settings.desktop-app.download', 'macos'))
        ->assertNotFound();
});

it('streams the installer for a supported os', function () {
    config(['services.github.token' => 'test-token']);
    Http::fake([
        'api.github.com/repos/*/releases/latest' => Http::response(fakeGithubDownloadRelease(), 200),
        'api.github.com/repos/*/releases/assets/*' => Http::response('binary-content', 200),
    ]);

    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->actingAs($user)
        ->get(route('settings.desktop-app.download', 'windows'));

    $response->assertOk();
    $response->assertHeader('Content-Disposition', 'attachment; filename="syaroushi-desktop-notifier_0.1.0_x64_en-US.msi"');
    expect($response->getContent())->toBe('binary-content');
});
