<?php

namespace AgeGate\Admin\Tools;

use AgeGate\Common\Admin\Helper;
use AgeGate\Common\Immutable\Constants as Immutable;

class Export
{
    use Helper;

    public const PERMISSION = Immutable::EXPORT;

    public function __construct()
    {
        add_action('admin_post_age_gate_export', [$this, 'action']);
    }

    public function action()
    {
        $postData = wp_unslash($_POST ?? []);

        if (!current_user_can(self::PERMISSION) || !wp_verify_nonce($postData['ag_export'] ?? '', 'ag_export')) {
            wp_die('Disallowed action');
        }

        if (empty($postData['ag_settings'])) {
            $this->redirect($postData['_wp_http_referer'], 0, 'tools');

        }

        $data = [
            'version' => AGE_GATE_VERSION,
            'options' => [],
        ];

        foreach ($postData['ag_settings'] as $option => $value) {
            if ($option === 'all') {
                continue;
            }

            if ($option === 'access') {
                global $wp_roles;

                $access = [];

                foreach (Immutable::AGE_GATE_ADMIN_PERMISSION as $key => $cap) {
                    $access[$key] = collect($wp_roles->roles)->map(fn($role) => array_key_exists($cap, $role['capabilities'] ?? []) ? $role['name'] : false)->filter()->toArray();
                }
                // foreach ($)
                $data['options'][$option] = $access;

            } else {
                $data['options'][$option] = get_option(Immutable::AGE_GATE_OPTIONS[$option], []);
            }
        }

        $file = 'age-gate-export-' . date('Y-m-d') . '.json'; // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date

        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename={$file}");
        header("Content-Type: application/json; charset=utf-8");

        // return
        echo json_encode($data, JSON_PRETTY_PRINT);
        die;

    }
}
