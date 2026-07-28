<?php

namespace App\Http\Controllers\Api;

use App\Enums\GuardianRegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CompleteActivationRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Requests\Api\ValidateActivationRequest;
use App\Http\Resources\CenterResource;
use App\Http\Resources\ChildSummaryResource;
use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use App\Support\GuardianActivationToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Step one of the welcome email: show the parent who they are about to
     * activate, so the SPA can render the form before asking for a password.
     */
    public function validateActivation(ValidateActivationRequest $request): JsonResponse
    {
        $guardian = GuardianActivationToken::resolve($request->string('token')->value());

        if (! $guardian) {
            throw ValidationException::withMessages([
                'token' => 'This activation link is no longer valid. Ask your center to send a new one.',
            ]);
        }

        return response()->json([
            'guardian' => [
                'first_name' => $guardian->first_name,
                'last_name' => $guardian->last_name,
                'email' => $guardian->email,
            ],
            'min_password_length' => config('parent.min_password_length'),
        ]);
    }

    /** Step two: set name + password, mark registered, hand back a JWT. */
    public function completeActivation(CompleteActivationRequest $request): JsonResponse
    {
        $guardian = GuardianActivationToken::resolve($request->string('token')->value());

        if (! $guardian) {
            throw ValidationException::withMessages([
                'token' => 'This activation link is no longer valid. Ask your center to send a new one.',
            ]);
        }

        [$firstName, $lastName] = Guardian::splitName($request->string('name')->value());

        $guardian->forceFill([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => $request->string('password')->value(),
            'email_verified_at' => now(),
            'registration_status' => GuardianRegistrationStatus::Registered,
            'last_login_at' => now(),
        ])->save();

        return $this->tokenResponse($guardian, auth('guardian')->login($guardian), Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $guardian = $this->findLoginCandidate(
            $request->string('email')->value(),
            $request->string('password')->value(),
        );

        if (! $guardian) {
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $guardian->forceFill(['last_login_at' => now()])->save();

        return $this->tokenResponse($guardian, auth('guardian')->login($guardian));
    }

    public function refresh(): JsonResponse
    {
        // Rotates: refresh() blacklists the token it was called with.
        $token = auth('guardian')->refresh();

        return $this->tokenResponse(auth('guardian')->user(), $token);
    }

    public function logout(): Response
    {
        auth('guardian')->logout();

        return response()->noContent();
    }

    /** Everything the SPA needs on load: who, which children, which center. */
    public function me(): JsonResponse
    {
        $guardian = auth('guardian')->user();

        $children = $guardian->children()
            ->with(['center', 'enrollments.classroom'])
            ->orderBy('children.first_name')
            ->get();

        return response()->json([
            'guardian' => new GuardianResource($guardian),
            'children' => ChildSummaryResource::collection($children),
            'center' => new CenterResource($guardian->center),
        ]);
    }

    /**
     * Always 200, whatever the address: this endpoint is unauthenticated,
     * and a distinguishable response would turn it into a way to find out
     * which parents are enrolled at a center.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->string('email')->value();

        // Only activated accounts get a reset link. An invited-but-not-yet
        // activated guardian has no password to reset — resetting one would
        // leave them with credentials that canLogIn() still refuses, so they
        // are sent back to their activation link instead.
        $eligible = Guardian::where('email', $email)
            ->get()
            ->contains(fn (Guardian $guardian) => $guardian->canLogIn());

        if ($eligible) {
            Password::broker('guardians')->sendResetLink(['email' => $email]);
        }

        return response()->json([
            'message' => 'If that email belongs to a parent account, a reset link is on its way.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('guardians')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Guardian $guardian, string $password) {
                $guardian->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($guardian));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['message' => __($status)]);
    }

    /**
     * `guardians.email` carries no unique index — the admin console matches
     * guardians by email *within a center*, so one parent with children at
     * two centers legitimately has two rows. Whichever of them the password
     * actually belongs to is the one logging in.
     */
    private function findLoginCandidate(string $email, string $password): ?Guardian
    {
        return Guardian::where('email', $email)
            ->where('registration_status', GuardianRegistrationStatus::Registered)
            ->whereNotNull('password')
            ->get()
            ->first(fn (Guardian $guardian) => Hash::check($password, $guardian->password));
    }

    private function tokenResponse(Guardian $guardian, string $token, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('guardian')->factory()->getTTL() * 60,
            'guardian' => new GuardianResource($guardian),
        ], $status);
    }
}
