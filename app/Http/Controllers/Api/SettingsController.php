<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateEmailSettingsRequest;
use App\Http\Requests\Api\UpdateNotificationSettingsRequest;
use App\Http\Requests\Api\UpdatePasswordSettingsRequest;
use App\Http\Requests\Api\UpdateProfileSettingsRequest;
use App\Models\Guardian;
use App\Models\GuardianNotificationPreference;
use Illuminate\Http\JsonResponse;

/**
 * The settings screen (S18). Every endpoint acts on the authenticated
 * guardian and nobody else — there is deliberately no {id} anywhere here.
 */
class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $guardian = $this->guardian();

        return response()->json([
            'profile' => $this->profileBlock($guardian),
            'email' => $this->emailBlock($guardian),
            'notifications' => $this->notificationsBlock($guardian),
        ]);
    }

    public function updateProfile(UpdateProfileSettingsRequest $request): JsonResponse
    {
        $guardian = $this->guardian();

        [$firstName, $lastName] = Guardian::splitName($request->string('name')->value());
        $guardian->update(['first_name' => $firstName, 'last_name' => $lastName]);

        return response()->json(['profile' => $this->profileBlock($guardian)]);
    }

    public function updateEmail(UpdateEmailSettingsRequest $request): JsonResponse
    {
        $guardian = $this->guardian();

        $guardian->update([
            'email' => $request->string('email')->value(),
            'receive_fewer_emails' => $request->boolean('receive_fewer_emails', $guardian->receive_fewer_emails),
            'email_language' => $request->input('email_language', $guardian->email_language),
        ]);

        return response()->json(['email' => $this->emailBlock($guardian)]);
    }

    public function updatePassword(UpdatePasswordSettingsRequest $request): JsonResponse
    {
        // `password` is a hashed cast; assigning the plain string hashes it.
        $this->guardian()->forceFill([
            'password' => $request->string('password')->value(),
        ])->save();

        return response()->json(['message' => 'Password updated.']);
    }

    public function updateNotifications(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $guardian = $this->guardian();

        GuardianNotificationPreference::updateOrCreate(
            ['guardian_id' => $guardian->getKey()],
            $request->validated(),
        );

        return response()->json(['notifications' => $this->notificationsBlock($guardian->fresh())]);
    }

    /** @return array<string, mixed> */
    private function profileBlock(Guardian $guardian): array
    {
        return [
            'name' => $guardian->fullName(),
            // The username IS the email; the screen shows it read-only.
            'username' => $guardian->email,
        ];
    }

    /** @return array<string, mixed> */
    private function emailBlock(Guardian $guardian): array
    {
        return [
            'email' => $guardian->email,
            'receive_fewer_emails' => (bool) $guardian->receive_fewer_emails,
            'email_language' => $guardian->email_language,
        ];
    }

    /** @return array<string, bool> */
    private function notificationsBlock(Guardian $guardian): array
    {
        // Everything defaults to on; the row only exists once the guardian
        // first reads or writes their settings. Defaults passed explicitly —
        // a just-created model never sees the column defaults the DB holds.
        $preference = $guardian->notificationPreference()->firstOrCreate(
            [],
            array_fill_keys(UpdateNotificationSettingsRequest::TOGGLES, true),
        );

        return $preference->only(UpdateNotificationSettingsRequest::TOGGLES);
    }

    private function guardian(): Guardian
    {
        return auth('guardian')->user();
    }
}
