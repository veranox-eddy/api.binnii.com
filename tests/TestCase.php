<?php

namespace Tests;

use App\Enums\ChildGuardianType;
use App\Models\Child;
use App\Models\Guardian;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\JWT;

abstract class TestCase extends BaseTestCase
{
    /**
     * Feature tests reuse one container for every request in a test method,
     * but a real request gets a fresh one. Without this, JWTGuard hands back
     * the user it resolved on the previous call and the JWT service keeps
     * the token it parsed then — so a token invalidated mid-test (logout,
     * refresh) would keep working and the test would pass on a lie.
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $cookies
     * @param  array<string, mixed>  $files
     * @param  array<string, mixed>  $server
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();
        $this->app[JWT::class]->unsetToken();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * Authenticate as a guardian the way the SPA does — a real JWT in the
     * Authorization header, not a faked guard. A test that skips this and
     * still gets a 200 is telling you the route is unprotected.
     */
    protected function actingAsGuardian(Guardian $guardian): static
    {
        return $this->withToken(JWTAuth::fromUser($guardian));
    }

    /**
     * Link a guardian to a child with the parent-API pivot flags.
     *
     * @param  array<string, mixed>  $flags
     */
    protected function linkGuardianToChild(Guardian $guardian, Child $child, array $flags = []): void
    {
        $guardian->children()->attach($child, [
            'type' => ChildGuardianType::Parent->value,
            'relationship' => null,
            'is_emergency' => false,
            'priority' => 1,
            'is_account_admin' => false,
            'has_full_photo_access' => true,
            'nickname' => null,
            ...$flags,
        ]);
    }
}
