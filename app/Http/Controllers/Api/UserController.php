<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(
            User::withCount('events')->orderBy('name')->get()
        );
    }

    public function toggleApprove(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user->update(['approved' => ! $user->approved]);

        return response()->json($user->fresh()->loadCount('events'));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete yourself.'], 422);
        }

        $user->delete();

        return response()->json(null, 204);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $changingEmail    = $request->has('email') && $request->input('email') !== $user->email;
        $changingPassword = $request->filled('password');

        $rules = [
            'name'               => ['sometimes', 'string', 'max:255'],
            'email'              => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'preferred_language' => ['sometimes', 'in:de,en,fr,zh'],
            'password'           => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($changingEmail || $changingPassword) {
            $rules['current_password'] = ['required', 'string'];
        }

        $data = $request->validate($rules);

        if (! empty($data['current_password'])) {
            if (! Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Aktuelles Passwort ist falsch.',
                    'errors'  => ['current_password' => ['Das aktuelle Passwort ist falsch.']],
                ], 422);
            }
        }

        unset($data['current_password']);

        // Remove password key if it's null/empty (only update when explicitly set)
        if (array_key_exists('password', $data) && empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->fresh());
    }
}
