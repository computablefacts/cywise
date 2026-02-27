<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Privacy Settings
    |--------------------------------------------------------------------------
    |
    | These are the default privacy settings applied to new users.
    |
    */

    'defaults' => [
        'profile_visibility' => 'private', // public, private
        'show_email' => false,
        'allow_search_engines' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile Visibility Options
    |--------------------------------------------------------------------------
    |
    | Available options for profile visibility settings.
    |
    */

    'visibility_options' => [
        'public' => 'Public - Anyone can view your profile',
        'private' => 'Private - Only you can view your profile',
    ],

];
