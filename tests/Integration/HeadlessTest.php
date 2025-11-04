<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Invelity\WizardPackage\Tests\Fixtures\PersonalInfoStep;

beforeEach(function () {
    Config::set('wizard.wizards.test-wizard.steps', [
        PersonalInfoStep::class,
    ]);
});

test('controllers return json not views', function () {
    $response = $this->get('/wizard/test-wizard/personal-info');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
});

test('step processing returns json', function () {
    $this->get('/wizard/test-wizard/personal-info');

    $response = $this->post('/wizard/test-wizard/personal-info', [
        'name' => 'John Doe',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
});

test('wizard completion returns json', function () {
    $this->get('/wizard/test-wizard/personal-info');

    $this->post('/wizard/test-wizard/personal-info', [
        'name' => 'John Doe',
    ]);

    $response = $this->post('/wizard/test-wizard/complete');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
});

test('no view content in responses', function () {
    $response = $this->get('/wizard/test-wizard/personal-info');

    $response->assertStatus(200);
    expect($response->getContent())->not->toContain('<html>');
    expect($response->getContent())->not->toContain('<!DOCTYPE');
});
