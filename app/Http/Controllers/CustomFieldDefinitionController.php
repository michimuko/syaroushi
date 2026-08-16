<?php

namespace App\Http\Controllers;

use App\Enums\CustomFieldTarget;
use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * カスタムフィールド定義の管理画面（企画書5-E）。フィールド定義の追加・編集・削除はowner限定（企画書7.5章）。
 */
class CustomFieldDefinitionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', CustomFieldDefinition::class);

        return Inertia::render('Settings/CustomFields/Index', [
            'definitions' => CustomFieldDefinition::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CustomFieldDefinition::class);

        $validated = $request->validate([
            'target' => ['required', Rule::enum(CustomFieldTarget::class)],
            'label' => 'required|string|max:255',
            'field_type' => ['required', Rule::enum(CustomFieldType::class)],
            'options' => 'required_if:field_type,select|array|min:1',
            'options.*' => 'required|string|max:255|distinct',
        ]);

        CustomFieldDefinition::create([
            'target' => $validated['target'],
            'label' => $validated['label'],
            'field_type' => $validated['field_type'],
            'options' => $validated['field_type'] === CustomFieldType::Select->value ? $validated['options'] : null,
        ]);

        return back()->with('success', 'カスタムフィールドを追加しました。');
    }

    public function update(Request $request, CustomFieldDefinition $customFieldDefinition): RedirectResponse
    {
        $this->authorize('update', $customFieldDefinition);

        $isSelect = $customFieldDefinition->field_type === CustomFieldType::Select;

        $rules = ['label' => 'required|string|max:255'];
        if ($isSelect) {
            $rules['options'] = 'required|array|min:1';
            $rules['options.*'] = 'required|string|max:255|distinct';
        }

        $validated = $request->validate($rules);

        $customFieldDefinition->update([
            'label' => $validated['label'],
            ...($isSelect ? ['options' => $validated['options']] : []),
        ]);

        return back()->with('success', 'カスタムフィールドを更新しました。');
    }

    public function destroy(CustomFieldDefinition $customFieldDefinition): RedirectResponse
    {
        $this->authorize('delete', $customFieldDefinition);

        $customFieldDefinition->delete();

        return back()->with('success', 'カスタムフィールドを削除しました。');
    }
}
