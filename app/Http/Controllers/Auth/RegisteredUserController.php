<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * 新規登録は「事務所（テナント）の新規契約」を意味するため、
     * officeを新規作成し、登録者をそのofficeのownerとして扱う。
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'office_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'login_id' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:users,login_id'],
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $office = Office::create([
                'name' => $request->office_name,
            ]);

            // office_idはOffice::create()直後の信頼できる値。AssignsOfficeOnCreateの
            // 「認証中のwebガードユーザーの事務所に強制」フックが割り込むと事故になるため止める
            // （このルートはguest:webでガードされ通常webガード認証中はあり得ないが、念のため）。
            return User::withoutEvents(fn () => User::create([
                'office_id' => $office->id,
                'name' => $request->name,
                'login_id' => $request->login_id,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => UserRole::Owner,
            ]));
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
