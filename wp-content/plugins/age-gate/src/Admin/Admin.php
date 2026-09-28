<?php

namespace AgeGate\Admin;

use WP_REST_Request;
use Asylum\Utility\Notice;
use AgeGate\Common\Settings;
use Asylum\Utility\Language;
use AgeGate\Admin\User\Toolbar;
use AgeGate\Common\Immutable\Constants;
use AgeGate\Admin\Controller\PostController;
use AgeGate\Admin\Controller\ToolsController;
use AgeGate\Admin\Controller\AccessController;
use AgeGate\Admin\Controller\ContentController;
use AgeGate\Admin\Controller\MessageController;
use AgeGate\Admin\Controller\AdvancedController;
use AgeGate\Admin\Controller\AppearanceController;
use AgeGate\Admin\Controller\RestrictionController;
use AgeGate\Admin\Controller\TroubleShootingController;

class Admin
{
    public function __construct()
    {
        // add_action('init', fn() => dd(Language::getInstance()->getLanguages()));
        add_action('init', function () {
            new RestrictionController();
            new MessageController();
            new AppearanceController();
            new AdvancedController();
            new AccessController();
            new ContentController();
            new ToolsController();
            new PostController();
            new Toolbar();
            new TroubleShootingController();
        });

        add_action('admin_notices', [$this, 'notices']);

        $basename = plugin_basename(AGE_GATE_PATH . 'age-gate.php');
        add_filter("plugin_action_links_" . $basename, [$this, 'actionLinks']);
        add_filter('plugin_row_meta', [$this, 'websiteLink'], 10, 2);
    }


    public function notices()
    {
        global $pagenow, $plugin_page;
        // $req = wp_remote_get(rest_url('/age-gate/v3/check'), [
        //     'sslverify' => false
        // ]);

        if (!is_php_version_compatible('7.4') && strpos(sanitize_text_field($plugin_page ?: ''), 'age-gate') !== false) {
             echo '<div id="message" class="notice notice-error"><p>' . esc_html__('Age Gate requires a minimum PHP version of 7.4 which your system does not have. You may experience some issues.', 'age-gate') . '</p></div>';
        }

        // if (wp_remote_retrieve_response_code($req) !== 200 && current_user_can(Constants::RESTRICTIONS) && strpos(sanitize_text_field($plugin_page ?: ''), 'age-gate-advanced') !== false) {
        //     echo '<div id="ag-api-error" class="notice notice-error is-dismissible"><p>' . esc_html__('Age Gate is having trouble contacting the Wordpress REST API. Is something blocking it?', 'age-gate') . '</p></div>';
        // }

        // is it an age gate page?
        // TODO: replace with notice class. Why is it like this?
        if ($pagenow === 'admin.php' && isset($_GET['m']) && strpos((sanitize_text_field($plugin_page ?: '')), 'age-gate') === 0) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            switch ((int) $_GET['m']) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                case 1:
                    $status = 'success';
                    $message = esc_html__('Settings saved', 'age-gate');
                    break;
                case 0:
                    $status = 'error';
                    $message = esc_html__('Something went wrong', 'age-gate');
                    break;
            }
            echo sprintf('<div class="notice notice-%s"><p>%s</p></div>', esc_attr($status), esc_html($message));
        }

        if (Settings::getInstance()->disableAgeGate && current_user_can(Constants::TOOLS)) {
            echo '<div id="message" class="notice notice-warning"><p>' . esc_html__('Age Gate is currently disabled in the Tools section and will not show for any users', 'age-gate') . '</p></div>';
        }

        foreach (Notice::get() ?? [] as $notice) {
            echo '<div id="message" class="notice notice-' . esc_attr($notice['type'] ?? 'notice') . '"><p>' . esc_html($notice['message']) . '</p></div>';
        }

        if (is_plugin_active('age-gate-user-registration/age-gate-user-registration.php')) {

            $data = get_plugin_data(WP_PLUGIN_DIR . '/age-gate-user-registration/age-gate-user-registration.php');

            if ($data['Version'] ?? false) {
                if (version_compare($data['Version'], '1.0.0', '<')) {
                    /* translators: 1: Age Gate User Registration version. 2: Aga gate version */
                    echo '<div id="message" class="notice notice-error"><p>' . esc_html(sprintf(__('Your version of Age Gate User Registration (%1$s) is not compatible with Age Gate %2$s. Please download a supported version from our website', 'age-gate'), $data['Version'], AGE_GATE_VERSION)) . '</p></div>';
                }
            }
        }

    }

    public function actionLinks($links)
    {
        $settings = sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=age-gate'), esc_html__('Settings', 'age-gate'));
        $donate = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=donate%40wordpressagegate%2ecom&lc=GB&item_name=Age%20Gate&item_number=Age%20Gate%20Donation&no_note=0&currency_code=GBP&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'age-gate') . '</a>';

        array_unshift($links, $settings);
        array_push($links, $donate);

        return $links;
    }

    public function websiteLink($meta, $file)
    {
        $basename = plugin_basename(AGE_GATE_PATH) . '/age-gate.php';

        if ($basename === $file) {
            $meta[] = sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                'https://agegate.io/docs',
                esc_html__('Documentation', 'age-gate')
            );

            $meta[] = sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                'https://agegate.io/release-notes',
                esc_html__('What&rsquo;s new?', 'age-gate')
            );
        }

        return $meta;
    }
}
