<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;

/**
 * 顧問先向け進捗レポート（企画書5-B）のPDFを生成する。
 * 日本語表示のためIPAゴシック（resources/fonts/ipag.ttf）をCSSの@font-faceで埋め込む
 * （dompdf標準のDejaVu Sansは日本語グリフを持たないため）。
 */
class ClientReportPdfGenerator
{
    public function generate(Client $client, CarbonImmutable $periodStart, CarbonImmutable $periodEnd, ?string $comment, User $generatedBy): string
    {
        $tasks = $client->tasksDueBetween($periodStart, $periodEnd);

        $pdf = Pdf::loadView('reports.client-progress', [
            'office' => $client->office,
            'client' => $client,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'comment' => $comment,
            'tasks' => $tasks,
            'generatedAt' => now(),
            'generatedByName' => $generatedBy->name,
            'fontPath' => 'file://'.resource_path('fonts/ipag.ttf'),
        ]);

        return $pdf->output();
    }
}
