<?php

use App\Helpers\Snippet;

test('searched words not in text', function () {
    $snippets = Snippet::extract(["not_here"], "Hello world!");
    expect($snippets->isEmpty())->toBeTrue();
});

test('text shorter than snippet max length', function () {
    $snippets = Snippet::extract(["world"], "Hello world!");
    expect($snippets->first())->toEqual("Hello world!");
});

test('snippet with left ellipsis', function () {
    $snippets = Snippet::extract(["Yahoo", "Outlook"], text());
    expect($snippets->first())->toEqual("...in-the-know with the latest news and information. CloudSponge provides an interface to easily enable your users to import contacts from a variety of the most popular webmail services including Yahoo, Gmail and Hotmail/MSN as well as popular desktop address books such as Mac Address Book and Outlook.");
});

test('snippet without left ellipsis', function () {
    $snippets = Snippet::extractEx(["Yahoo", "Outlook"], text(), 300, 50, "");
    expect($snippets->first())->toEqual("in-the-know with the latest news and information. CloudSponge provides an interface to easily enable your users to import contacts from a variety of the most popular webmail services including Yahoo, Gmail and Hotmail/MSN as well as popular desktop address books such as Mac Address Book and Outlook.");
});

test('snippet with right ellipsis', function () {
    $snippets = Snippet::extract(["most", "visited", "home", "page"], text());
    expect($snippets->first())->toEqual("Welcome to Yahoo!, the world’s most visited home page. Quickly find what you’re searching for, get in touch with friends and stay in-the-know with the latest news and information. CloudSponge provides an interface to easily enable your users to import contacts from a variety of the most popular webmail...");
});

test('snippet with left and right ellipsis', function () {
    $snippets = Snippet::extract(["latest", "news", "CloudSponge"], text());
    expect($snippets->first())->toEqual("...in touch with friends and stay in-the-know with the latest news and information. CloudSponge provides an interface to easily enable your users to import contacts from a variety of the most popular webmail services including Yahoo, Gmail and Hotmail/MSN as well as popular desktop address books such as Mac...");
});

test('snippet with empty indicator', function () {
    $snippets = Snippet::extractEx(["latest", "news", "CloudSponge"], text(), 300, 50, "");
    expect($snippets->first())->toEqual("in touch with friends and stay in-the-know with the latest news and information. CloudSponge provides an interface to easily enable your users to import contacts from a variety of the most popular webmail services including Yahoo, Gmail and Hotmail/MSN as well as popular desktop address books such as Mac");
});

test('snippet with null indicator', function () {
    $snippets = Snippet::extractEx(["latest", "news", "CloudSponge"], text(), 300, 50, null);
    expect($snippets->first())->toEqual("...in touch with friends and stay in-the-know with the latest news and information. CloudSponge provides an interface to easily enable your users to import contacts from a variety of the most popular webmail services including Yahoo, Gmail and Hotmail/MSN as well as popular desktop address books such as Mac...");
});

test('do not truncate head', function () {
    $snippets = Snippet::extractEx(["gmail"], "zor@gmail.com", 5, 0, "...");
    expect($snippets->first())->toEqual("...gmail...");
});

test('do not truncate head with null indicator', function () {
    $snippets = Snippet::extractEx(["gmail"], "zor@gmail.com", 5, 0, null);
    expect($snippets->first())->toEqual("...gmail...");
});

test('do not truncate head with empty indicator', function () {
    $snippets = Snippet::extractEx(["gmail"], "zor@gmail.com", 5, 0, "");
    expect($snippets->first())->toEqual("gmail");
});

test('do not truncate tail', function () {
    $snippets = Snippet::extractEx(["zor"], "zor@gmail.com", 3, 50, "...");
    expect($snippets->first())->toEqual("zor@gmail.com");
});

test('do not truncate tail with null indicator', function () {
    $snippets = Snippet::extractEx(["zor"], "zor@gmail.com", 3, 50, null);
    expect($snippets->first())->toEqual("zor@gmail.com");
});

test('do not truncate tail with empty indicator', function () {
    $snippets = Snippet::extractEx(["zor"], "zor@gmail.com", 3, 50, "");
    expect($snippets->first())->toEqual("zor@gmail.com");
});

function text(): string
{
    return "Welcome to Yahoo!, the world’s most visited home page. Quickly find what you’re searching for, get in touch with friends and stay in-the-know with the latest news and information. CloudSponge provides an interface to easily enable your users to import contacts from a variety of the most popular webmail services including Yahoo, Gmail and Hotmail/MSN as well as popular desktop address books such as Mac Address Book and Outlook.";
}
