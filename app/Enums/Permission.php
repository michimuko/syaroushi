<?php

namespace App\Enums;

/**
 * owner限定の操作のうちstaffへ個別委譲できるもの（企画書7.5章）。
 * 顧問先削除・ユーザー管理は委譲対象に含めない（権限昇格リスク・影響範囲の大きさのため）。
 */
enum Permission: string
{
    case ManageProcedureTypes = 'manage_procedure_types';
    case ManageCustomFields = 'manage_custom_fields';
    case ManageImports = 'manage_imports';
}
