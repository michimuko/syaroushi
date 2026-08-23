<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ComputesHighlightPage;
use App\Models\User;
use App\Notifications\UserAccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use ComputesHighlightPage;

    /**
     * Display a listing of the resource.
     *
     * Userモデルはoffice_idのGlobal Scopeを持たないため（app/Models/User.php参照）、
     * 必ずAuth::user()->office->users()経由でクエリし、他事務所のユーザーが
     * 一覧に混ざらないようにする。
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $users = Auth::user()->office->users()
            ->when(
                $validated['search'] ?? null,
                fn ($query, $search) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                ),
            )
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $validated['search'] ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'login_id' => [
                'required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_.-]+$/',
                Rule::unique('users', 'login_id')->where('office_id', Auth::user()->office_id),
            ],
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['required', 'confirmed', 'string', 'min:8'],
            'permissions' => ['array'],
            'permissions.*' => [Rule::enum(Permission::class)],
        ]);

        // ownerは常に全権限を持つため、role=ownerで登録する場合はpermissionsを空にする
        $validated['permissions'] = $validated['role'] === UserRole::Owner->value
            ? []
            : ($validated['permissions'] ?? []);

        // office_idはAssignsOfficeOnCreateがログイン中の事務所で自動付与する
        $user = User::create($validated);

        $user->notify(new UserAccountCreated(Auth::user()->office));

        $page = $this->pageContainingId(
            Auth::user()->office->users()->orderBy('role')->orderBy('name'),
            $user->id,
        );

        return redirect()->route('users.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', 'ユーザーを登録しました。')
            ->with('highlightId', $user->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Edit', [
            'targetUser' => $user->only(['id', 'name', 'login_id', 'email', 'role', 'permissions']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'login_id' => [
                'required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_.-]+$/',
                Rule::unique('users', 'login_id')->where('office_id', Auth::user()->office_id)->ignore($user->id),
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'permissions' => ['array'],
            'permissions.*' => [Rule::enum(Permission::class)],
        ]);

        if (
            $user->isOwner()
            && $validated['role'] === UserRole::Staff->value
            && Auth::user()->office->users()->where('role', UserRole::Owner)->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => '事務所に最低1名のオーナーが必要なため、降格できません。',
            ]);
        }

        // ownerは常に全権限を持つため、role=ownerに設定する場合はpermissionsを空にする
        $validated['permissions'] = $validated['role'] === UserRole::Owner->value
            ? []
            : ($validated['permissions'] ?? []);

        $user->update($validated);

        $page = $this->pageContainingId(
            Auth::user()->office->users()->orderBy('role')->orderBy('name'),
            $user->id,
        );

        return redirect()->route('users.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', 'ユーザー情報を更新しました。')
            ->with('highlightId', $user->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')->with('success', 'ユーザーを削除しました。');
    }
}
