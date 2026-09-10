<?php

it('supports parent-preserving copies', function () {
    $dockerfile = file_get_contents(base_path('towerify/Dockerfile'));

    expect($dockerfile)->toStartWith('# syntax=docker/dockerfile:1.20');
});
