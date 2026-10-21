<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_landing_page_redirects_to_notes(): void
    {
        $this->get('/')->assertRedirect('/notes');
    }
}
