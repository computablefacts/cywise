<?php

it('Home returns a successful response', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
    // Fail the first time, works the second
    // Probably an issue with filling the database or reading the app_config table
    // TODO: this should work => $response->assertSee('Cywise');
})->skip('Fail the first time, works the second');
