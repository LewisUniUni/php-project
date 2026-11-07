<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class SessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $remember = $request->boolean('remember');

        if (! Auth::attempt($request->safe()->only(['email', 'password']), $remember)) {
            return back()->withErrors([
                'email' => 'Those credentials do not match an operator account.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('warehouse.stock');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
