<?php

use App\Services\Import\ImportWizardService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    Storage::fake('local');
    $this->service = new ImportWizardService;
});

it('stores a temp file scoped to the office and resolves it back by token', function () {
    $file = UploadedFile::fake()->createWithContent('clients.csv', "name\nアルファ商事\n");

    $token = $this->service->storeTempFile(officeId: 1, file: $file);

    $path = $this->service->resolvePath(officeId: 1, token: $token);

    expect(file_get_contents($path))->toContain('アルファ商事')
        ->and($path)->toContain('imports/1/'.$token);
});

it('cannot resolve a token under a different office id', function () {
    $file = UploadedFile::fake()->createWithContent('clients.csv', "name\nアルファ商事\n");
    $token = $this->service->storeTempFile(officeId: 1, file: $file);

    $this->service->resolvePath(officeId: 2, token: $token);
})->throws(NotFoundHttpException::class);

it('deletes a temp file so it can no longer be resolved', function () {
    $file = UploadedFile::fake()->createWithContent('clients.csv', "name\nアルファ商事\n");
    $token = $this->service->storeTempFile(officeId: 1, file: $file);

    $this->service->deleteTempFile(officeId: 1, token: $token);

    $this->service->resolvePath(officeId: 1, token: $token);
})->throws(NotFoundHttpException::class);
