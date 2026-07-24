<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\Feedback;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $key = 'feedback:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['message' => 'Zu viele Anfragen.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'name'    => ['nullable', 'string', 'max:255'],
            'email'   => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $adminEmail = User::where('is_admin', true)->value('email') ?? config('mail.from.address');

        Mail::to($adminEmail)
            ->send(new Feedback($data['message'], $data['name'] ?? null, $data['email'] ?? null));

        return response()->json(['message' => 'Feedback gesendet.']);
    }
}
