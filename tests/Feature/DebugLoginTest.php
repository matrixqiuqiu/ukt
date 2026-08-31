<?php

namespace Tests\Feature;

use Tests\TestCase;

class DebugLoginTest extends TestCase
{
    public function test_admin_login_redirect(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->post('/admin', [
            'email' => 'admin@ubt.ac.id',
            'password' => 'password',
        ]);

        fwrite(STDERR, 'LOGIN STATUS: ' . $response->getStatusCode() . PHP_EOL);
        fwrite(STDERR, 'LOGIN LOCATION: ' . ($response->headers->get('Location') ?? 'null') . PHP_EOL);
    }

    public function test_intended_after_protected_page(): void
    {
        // User tries to open a protected page first (intended URL gets saved)
        $this->get('/admin/dashboard');

        $response = $this->post('/admin', [
            'email' => 'admin@ubt.ac.id',
            'password' => 'password',
        ]);

        fwrite(STDERR, 'INTENDED STATUS: ' . $response->getStatusCode() . PHP_EOL);
        fwrite(STDERR, 'INTENDED LOCATION: ' . ($response->headers->get('Location') ?? 'null') . PHP_EOL);

        // Follow the redirect
        $follow = $this->get($response->headers->get('Location'));
        fwrite(STDERR, 'FOLLOW STATUS: ' . $follow->getStatusCode() . PHP_EOL);
    }
}
