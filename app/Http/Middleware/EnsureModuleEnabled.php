<?php

namespace App\Http\Middleware;

use App\Enums\Module;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * オプションモジュールが事務所で有効化されているかをチェックする（運営者がOffice::enabled_modulesで制御）。
 * ルート定義側で `module:calc_assistant` のようにModuleのvalueをパラメータで渡す。
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(
            $request->user()?->office?->hasModule(Module::from($module)) ?? false,
            403,
            'この機能は現在のプランでは利用できません。',
        );

        return $next($request);
    }
}
