<?php

namespace AgeGate\Presentation;

use AgeGate\Common\Settings;

class Interaction
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'assets'], 1);
    }

    public function assets()
    {
        $settings = Settings::getInstance();
        $path = sprintf('%s%s%s', AGE_GATE_URL, 'dist', ($settings->rawAssets ? '/raw' : ''));
        wp_enqueue_script('age-gate-interaction', $path . '/interaction.js', [], AGE_GATE_VERSION, !$settings->inHeader);
    }
}
