<?php

namespace App\Http\Requests\Auth;

use App\Models\Office;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'office_code' => ['required', 'string'],
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * login_idは事務所内でのみ一意（他事務所と重複しうる）ため、まず事務所コードで
     * 事務所を特定してから、その事務所に所属するユーザーとしてlogin_id・パスワードを照合する。
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $office = Office::query()->where('office_code', Str::lower($this->string('office_code')))->first();

        // 事業所IDが存在しない場合と認証情報が誤っている場合を、フィールド・文言ともに
        // 区別できないようにする（事業所IDの存在有無を推測されるのを防ぐため）。
        if (! $office) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login_id' => trans('auth.failed'),
            ]);
        }

        if (! Auth::attempt([
            'office_id' => $office->id,
            'login_id' => $this->string('login_id'),
            'password' => $this->string('password'),
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login_id' => trans('auth.failed'),
            ]);
        }

        if (! $office->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'login_id' => 'ご利用の事務所は現在ご利用いただけません。運営事務局へお問い合わせください。',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login_id' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('office_code')).'|'.Str::lower($this->string('login_id')).'|'.$this->ip()
        );
    }
}
