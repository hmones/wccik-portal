<?php

namespace App\Http\Controllers\Public;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewMemberApplicationRequest;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function create(Request $request): Response
    {
        $a = random_int(1, 20);
        $b = random_int(1, 20);
        $request->session()->put('captcha_answer', $a + $b);

        return Inertia::render('Apply', [
            'captchaA' => $a,
            'captchaB' => $b,
        ]);
    }

    public function store(StoreNewMemberApplicationRequest $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return redirect()->route('apply.confirmation', ['token' => 'honeypot']);
        }

        $data = $request->validated();
        unset($data['captcha_answer'], $data['confirm_10_days'], $data['website']);

        $application = Application::create([
            ...$data,
            'type' => ApplicationType::NewMember,
            'status' => ApplicationStatus::Submitted,
            'has_ntn' => $request->boolean('has_ntn'),
            'ntn_number' => $request->boolean('has_ntn') ? ($data['ntn_number'] ?? null) : null,
            'submitted_at' => now(),
        ]);

        return redirect()->route('apply.confirmation', ['token' => $application->status_token]);
    }

    public function confirmation(string $token): Response
    {
        $application = Application::where('status_token', $token)->firstOrFail();

        return Inertia::render('ApplyConfirmation', [
            'token' => $token,
            'name' => $application->authorized_representative_name,
        ]);
    }
}
