<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_example(): void
    {
        $response = $this->get(route('timeline'));

        $response->assertRedirect(route('login'));
    }
}
