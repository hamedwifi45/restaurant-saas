<?php

use Illuminate\Support\Facades\Blade;

test('the welcome page uses local frontend assets', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSee(asset('vendor/fontawesome/css/all.min.css'))
        ->assertDontSee('cdn.tailwindcss.com')
        ->assertDontSee('fonts.googleapis.com');
});

test('QR codes are generated locally as SVG data URIs', function () {
    $html = Blade::render('<x-offline-qr-code value="https://demo.test/menu" alt="Demo QR" />');

    expect($html)
        ->toContain('src="data:image/svg+xml;base64,')
        ->toContain('alt="Demo QR"')
        ->not->toContain('api.qrserver.com');
});
