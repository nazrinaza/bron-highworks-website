<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PilotController extends Controller
{
    public function index(): View
    {
        return view('ops.pilots', ['pilots' => User::query()->where('role', 'pilot')->withCount(['assignedOperations', 'flightLogs'])->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'pilot_licence_no' => ['nullable', 'string', 'max:100'],
            'pilot_licence_expires_at' => ['nullable', 'date'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);
        User::create($data + ['role' => 'pilot', 'status' => 'active', 'is_admin' => false]);

        return back()->with('success', 'Pilot account created.');
    }

    public function status(Request $request, User $pilot): RedirectResponse
    {
        $this->ensurePilot($pilot);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])]]);
        $pilot->update($data);

        return back()->with('success', $data['status'] === 'active' ? 'Pilot account activated.' : 'Pilot account suspended.');
    }

    public function destroy(User $pilot): RedirectResponse
    {
        $this->ensurePilot($pilot);
        $pilot->delete();

        return back()->with('success', 'Pilot account deleted. Historical operation and logbook records remain available.');
    }

    private function ensurePilot(User $pilot): void
    {
        abort_unless($pilot->isPilot() && ! $pilot->isOperationsAdmin(), 404);
    }
}
