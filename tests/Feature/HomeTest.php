<?php

it('Home returns a successful response', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
    $response->assertSee('Cywise');
});
