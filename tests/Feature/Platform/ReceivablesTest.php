<?php

use App\Models\Office;
use App\Models\OfficeInvoice;
use App\Models\PlatformAdmin;

test('the index defaults to showing only unpaid invoices with an accurate summary', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create();

    $unpaid = OfficeInvoice::factory()->for($office)->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'amount' => 6800, 'paid_at' => null]);
    OfficeInvoice::factory()->for($office)->create(['period_start' => '2026-02-01', 'period_end' => '2026-02-28', 'amount' => 2800, 'paid_at' => now()]);

    $response = $this->actingAs($admin, 'platform')->get(route('platform.receivables.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', 'unpaid')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.id', $unpaid->id)
            ->where('summary.unpaidTotal', 6800)
            ->where('summary.unpaidCount', 1));
});

test('the status filter can switch between paid, unpaid, and all', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create();

    OfficeInvoice::factory()->for($office)->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'paid_at' => null]);
    $paid = OfficeInvoice::factory()->for($office)->create(['period_start' => '2026-02-01', 'period_end' => '2026-02-28', 'paid_at' => now()]);

    $paidOnly = $this->actingAs($admin, 'platform')->get(route('platform.receivables.index', ['status' => 'paid']));
    $paidOnly->assertInertia(fn ($page) => $page
        ->has('invoices.data', 1)
        ->where('invoices.data.0.id', $paid->id));

    $all = $this->actingAs($admin, 'platform')->get(route('platform.receivables.index', ['status' => 'all']));
    $all->assertInertia(fn ($page) => $page->has('invoices.data', 2));
});

test('the office name search narrows the results', function () {
    $admin = PlatformAdmin::factory()->create();
    $matchingOffice = Office::factory()->create(['name' => 'アルファ社会保険労務士事務所']);
    $otherOffice = Office::factory()->create(['name' => 'ベータ社会保険労務士事務所']);

    $matchingInvoice = OfficeInvoice::factory()->for($matchingOffice)->create(['paid_at' => null]);
    OfficeInvoice::factory()->for($otherOffice)->create(['paid_at' => null]);

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.receivables.index', ['search' => 'アルファ']));

    $response->assertInertia(fn ($page) => $page
        ->has('invoices.data', 1)
        ->where('invoices.data.0.id', $matchingInvoice->id));
});

test('markPaid sets paid_at on an unpaid invoice', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create();
    $invoice = OfficeInvoice::factory()->for($office)->create(['paid_at' => null]);

    $response = $this->actingAs($admin, 'platform')
        ->post(route('platform.receivables.mark-paid', $invoice->id));

    $response->assertRedirect();
    expect($invoice->refresh()->paid_at)->not->toBeNull();
});

test('markPaid on an already-paid invoice does not change the original paid_at (idempotent)', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create();
    $invoice = OfficeInvoice::factory()->for($office)->create(['paid_at' => now()->subDays(3)]);
    $originalPaidAt = $invoice->fresh()->paid_at;

    $this->actingAs($admin, 'platform')->post(route('platform.receivables.mark-paid', $invoice->id));

    expect($invoice->refresh()->paid_at->equalTo($originalPaidAt))->toBeTrue();
});
