<?php

namespace App\Enums;

/**
 * オプションモジュール（企画書5章）。事務所ごとにOffice::enabled_modulesで個別にON/OFFできる。
 * コア機能（顧問先/タスク/カレンダー/メール通知）はここに含めず常に有効。
 */
enum Module: string
{
    case ExcelMigration = 'excel_migration';
    case ClientReportPdf = 'client_report_pdf';
    case WebPush = 'web_push';
    case CalcAssistant = 'calc_assistant';
    case CustomFields = 'custom_fields';

    public function label(): string
    {
        return match ($this) {
            self::ExcelMigration => 'Excel移行支援',
            self::ClientReportPdf => '顧問先向けPDFレポート',
            self::WebPush => 'Web Push通知',
            self::CalcAssistant => '計算アシスタント',
            self::CustomFields => 'カスタムフィールド',
        };
    }
}
