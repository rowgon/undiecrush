<?php

namespace Essential_Addons_Elementor\Pro\Traits;

use \Essential_Addons_Elementor\Classes\Helper as HelperClass;

trait Pinterest_Feed
{
    /**
     * Whether more pins remain beyond the first page; controls load-more visibility.
     *
     * @var bool
     */
    protected $eael_pf_has_more = false;

    // OAuth runs through the EA proxy (app.essential-addons.com/pinterest) — the proxy holds the
    // shared app secret and performs the code→token exchange + refresh; this site never sees the secret.
    // WP-Cron refreshes ~10 min before expiry; get_current_pinterest_access_token() also refreshes inline within a 5-min buffer.

    public function init_pinterest_feed_hooks() {
        add_filter( 'eael/admin/modal/pinterestFeedSetting', [ $this, 'add_pinterest_feed_dashboard_settings' ] );
        // Shared-app model — no per-site App ID/Secret to save. OAuth callback uses a front-end rewrite.
        add_action( 'init', [ $this, 'register_pinterest_feed_rewrite' ] );
        add_filter( 'query_vars', [ $this, 'register_pinterest_feed_query_var' ] );
        add_action( 'template_redirect', [ $this, 'handle_pinterest_feed_oauth_callback' ] );
        add_action( 'admin_init', [ $this, 'handle_pinterest_feed_disconnect' ] );
        add_action( 'init', [ $this, 'init_pinterest_feed_token_refresh' ] );
        add_action( 'eael_pinterest_feed_token_refresh', [ $this, 'refresh_pinterest_feed_token_cron' ] );
    }

    public function register_pinterest_feed_rewrite() {
        add_rewrite_rule( '^eael-pinterest-auth/?$', 'index.php?eael_pinterest_auth=1', 'top' );

        // Auto-flush once per rewrite-schema version; bump the version string if the slug changes.
        // v2 also drops the now-unused per-site app credentials (shared-app model — proxy holds them).
        if ( 'v2' !== get_option( 'eael_pinterest_rewrite_version' ) ) {
            flush_rewrite_rules( false );
            delete_option( 'eael_pinterest_client_id' );
            delete_option( 'eael_pinterest_client_secret' );
            update_option( 'eael_pinterest_rewrite_version', 'v2' );
        }
    }

    public function register_pinterest_feed_query_var( $vars ) {
        $vars[] = 'eael_pinterest_auth';
        return $vars;
    }

    private function get_pinterest_feed_redirect_uri() {
        return home_url( '/eael-pinterest-auth/' );
    }

    /**
     * EA central OAuth proxy base URL. The proxy owns the shared Pinterest app (App ID +
     * Secret), builds the authorize URL, performs the code→token exchange, and relays
     * tokens back to the site. Customer sites hold NO Pinterest app credentials.
     */
    private function get_pinterest_proxy_url() {
        $proxy = defined( 'EAEL_PRO_PINTEREST_PROXY' ) ? EAEL_PRO_PINTEREST_PROXY : 'https://app.essential-addons.com/pinterest';
        return untrailingslashit( apply_filters( 'eael/pinterest_feed/proxy_url', $proxy ) );
    }

    /**
     * Build the OAuth `state` — carries the site's return URL (so the proxy knows where
     * to redirect) plus a nonce (CSRF guard on the return leg). URL-safe base64.
     */
    private function build_pinterest_feed_state() {
        $payload = wp_json_encode( [
            'r' => $this->get_pinterest_feed_redirect_uri(),
            'n' => wp_create_nonce( 'eael_pinterest_oauth' ),
        ] );
        return rtrim( strtr( base64_encode( $payload ), '+/', '-_' ), '=' );
    }

    public function handle_pinterest_feed_disconnect() {
        if ( ! isset( $_GET['eael_pinterest_disconnect'] ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        // CSRF guard — the disconnect link carries a nonce; reject forged requests.
        check_admin_referer( 'eael_pinterest_disconnect' );
        $this->disconnect_pinterest_feed();
        wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=eael-settings&eael_pinterest_disconnected=1' ) ) );
        exit;
    }

    public function add_pinterest_feed_dashboard_settings( $settings ) {
        $access_token  = sanitize_text_field( get_option( 'eael_pinterest_access_token', '' ) );
        $is_connected  = ! empty( $access_token );

        // Shared-app model: no manual App ID / Secret entry — one-click connect only.
        $accordion = [
            'title'  => __( 'Pinterest API', 'essential-addons-elementor' ),
            'icon'   => 'images/pinterest.svg',
            'isPro'  => true,
            'fields' => [],
        ];

        $settings['accordion']['pinterestFeed'] = $accordion;

        if ( empty( $access_token ) ) {
            // Awaiting authorization — illustrated connect screen.
            $settings['accordion']['pinterestFeed']['connect_intro'] = [
                'icon'         => 'images/pinterest.svg',
                'title'        => __( 'Pinterest API', 'essential-addons-elementor' ),
                'text'         => __( 'Click Connect with Pinterest. When the authorization window appears, click Give access to instantly link your profile.', 'essential-addons-elementor' ),
                'illustration' => 'images/pinterest-connect.png',
            ];
            $settings['accordion']['pinterestFeed']['auth_button'] = [
                'text' => __( 'Connect with Pinterest', 'essential-addons-elementor' ),
                'url'  => $this->get_pinterest_feed_auth_url(),
            ];
        } else {
            $user_info = get_option( 'eael_pinterest_user_info', [] );
            // Stored value can be a JSON string on some installs — normalize to array.
            if ( is_string( $user_info ) ) {
                $user_info = json_decode( $user_info, true );
            }
            if ( ! is_array( $user_info ) ) {
                $user_info = [];
            }
            $username = ! empty( $user_info['username'] ) ? $user_info['username'] : '';

            // Success heading shown above the connected account card.
            $settings['accordion']['pinterestFeed']['connected_notice'] = [
                'icon' => 'images/pinterest.svg',
                'text' => __( 'Pinterest account connected successfully', 'essential-addons-elementor' ),
            ];

            // Connected account card — avatar + handle + CONNECTED badge.
            $settings['accordion']['pinterestFeed']['profile'] = [
                'avatar' => ! empty( $user_info['profile_image'] ) ? esc_url( $user_info['profile_image'] ) : '',
                'icon'   => 'images/pinterest_dark.svg',
                'name'   => $username ? $username : __( 'Pinterest account', 'essential-addons-elementor' ),
                'badge'  => __( 'Connected', 'essential-addons-elementor' ),
            ];

            $settings['accordion']['pinterestFeed']['disconnect_button'] = [
                'text' => __( 'Disconnect', 'essential-addons-elementor' ),
                'url'  => esc_url_raw( add_query_arg(
                    [
                        'page'                      => 'eael-settings',
                        'eael_pinterest_disconnect' => 1,
                        '_wpnonce'                  => wp_create_nonce( 'eael_pinterest_disconnect' ),
                    ],
                    admin_url( 'admin.php' )
                ) ),
            ];
        }

        if ( isset( $_GET['eael_pinterest_success'] ) && ! $is_connected ) {
            $settings['accordion']['pinterestFeed']['status_message'] = [
                'text' => __( 'Pinterest account connected successfully!', 'essential-addons-elementor' ),
                'type' => 'success',
            ];
        } elseif ( isset( $_GET['eael_pinterest_disconnected'] ) ) {
            $settings['accordion']['pinterestFeed']['status_message'] = [
                'text'    => __( 'Your Pinterest profile is disconnected.', 'essential-addons-elementor' ),
                'type'    => 'error',
                'display' => 'toast',
            ];
        } elseif ( isset( $_GET['eael_pinterest_error'] ) ) {
            $last_error = get_option( 'eael_pinterest_last_error', '' );
            $settings['accordion']['pinterestFeed']['status_message'] = [
                'text' => ! empty( $last_error ) ? $last_error : __( 'Authorization failed. Please try again.', 'essential-addons-elementor' ),
                'type' => 'error',
            ];
        } elseif ( 'revoked' === get_option( 'eael_pinterest_connection_status', '' ) ) {
            $settings['accordion']['pinterestFeed']['status_message'] = [
                'text' => __( 'Your Pinterest connection was revoked. Please reconnect.', 'essential-addons-elementor' ),
                'type' => 'error',
            ];
        }

        return $settings;
    }

    /**
     * Connect URL — points at the proxy's connect endpoint, NOT Pinterest directly. The proxy
     * holds the App ID and redirects on to Pinterest with the correct registered redirect_uri.
     * `state` carries this site's return URL + nonce so the proxy can relay the result back.
     */
    private function get_pinterest_feed_auth_url() {
        return add_query_arg(
            [
                'action' => 'connect',
                'state'  => $this->build_pinterest_feed_state(),
            ],
            $this->get_pinterest_proxy_url() . '/'
        );
    }

    public function handle_pinterest_feed_oauth_callback() {
        if ( (int) get_query_var( 'eael_pinterest_auth' ) !== 1 ) {
            return;
        }

        // The proxy relays an error message when Pinterest authorization is denied/fails.
        if ( isset( $_GET['eael_pf_error'] ) ) {
            $relayed = sanitize_text_field( wp_unslash( $_GET['eael_pf_error'] ) );
            update_option( 'eael_pinterest_last_error', $relayed !== '' ? $relayed : __( 'Authorization failed. Please try again.', 'essential-addons-elementor' ) );
            wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=eael-settings&eael_pinterest_error=1' ) ) );
            exit;
        }

        // The proxy returns a one-time exchange token (never the access token in the URL).
        if ( ! isset( $_GET['eael_pf_exchange'] ) ) {
            return;
        }

        // Nonce (from `state`) is user-bound; only the admin who initiated the flow can complete it.
        $state = isset( $_GET['eael_pf_state'] ) ? sanitize_text_field( wp_unslash( $_GET['eael_pf_state'] ) ) : '';
        if ( ! wp_verify_nonce( $state, 'eael_pinterest_oauth' ) ) {
            update_option( 'eael_pinterest_last_error', __( 'Security check failed. Please try connecting again.', 'essential-addons-elementor' ) );
            wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=eael-settings&eael_pinterest_error=1' ) ) );
            exit;
        }

        delete_option( 'eael_pinterest_last_error' );

        $exchange_token = sanitize_text_field( wp_unslash( $_GET['eael_pf_exchange'] ) );
        $data           = $this->fetch_pinterest_feed_tokens_from_proxy( $exchange_token );

        if ( is_wp_error( $data ) ) {
            update_option( 'eael_pinterest_last_error', $data->get_error_message() );
            wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=eael-settings&eael_pinterest_error=1' ) ) );
            exit;
        }

        if ( ! empty( $data['access_token'] ) && ! empty( $data['refresh_token'] ) ) {
            $expires_in         = intval( $data['expires_in'] ?? 2592000 );
            $refresh_expires_in = intval( $data['refresh_token_expires_in'] ?? YEAR_IN_SECONDS );

            update_option( 'eael_pinterest_access_token', sanitize_text_field( $data['access_token'] ) );
            update_option( 'eael_pinterest_refresh_token', sanitize_text_field( $data['refresh_token'] ) );
            update_option( 'eael_pinterest_token_expires', time() + $expires_in );
            update_option( 'eael_pinterest_refresh_expires', time() + $refresh_expires_in );
            delete_option( 'eael_pinterest_connection_status' );
            delete_option( 'eael_pinterest_refresh_failures' );

            // Cache username so cache keys can hash on a stable identity instead of rotating tokens.
            $user_info = $this->fetch_pinterest_user_info( $data['access_token'], 0 );
            if ( ! empty( $user_info ) ) {
                update_option( 'eael_pinterest_user_info', $user_info );
            }

            $this->schedule_pinterest_feed_token_refresh( $expires_in );

            wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=eael-settings&eael_pinterest_success=1' ) ) );
            exit;
        }

        update_option( 'eael_pinterest_last_error', __( 'Authorization failed. Please try connecting again.', 'essential-addons-elementor' ) );
        wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=eael-settings&eael_pinterest_error=1' ) ) );
        exit;
    }

    /**
     * Swap the proxy's one-time exchange token for the real Pinterest tokens (server-to-server).
     * The proxy holds the app secret and performed the Pinterest code→token exchange already.
     *
     * @param string $exchange_token
     * @return array|\WP_Error  Token payload or WP_Error on failure.
     */
    private function fetch_pinterest_feed_tokens_from_proxy( $exchange_token ) {
        $response = wp_remote_post(
            $this->get_pinterest_proxy_url() . '/',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body'    => wp_json_encode( [
                    'action'         => 'exchange',
                    'exchange_token' => $exchange_token,
                    'site'           => home_url(),
                ] ),
                'timeout' => 30,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'eael_pf_proxy', __( 'Could not reach the Pinterest connection service. Please try again.', 'essential-addons-elementor' ) );
        }

        if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return new \WP_Error( 'eael_pf_proxy', __( 'Pinterest connection service returned an error. Please try again.', 'essential-addons-elementor' ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $data['access_token'] ) ) {
            return new \WP_Error( 'eael_pf_proxy', __( 'Pinterest connection could not be completed. Please try again.', 'essential-addons-elementor' ) );
        }

        return $data;
    }

    private function schedule_pinterest_feed_token_refresh( $expires_in ) {
        $timestamp = wp_next_scheduled( 'eael_pinterest_feed_token_refresh' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'eael_pinterest_feed_token_refresh' );
        }

        $refresh_time = time() + intval( $expires_in ) - 600;
        if ( $refresh_time <= time() ) {
            $refresh_time = time() + 60;
        }

        wp_schedule_single_event( $refresh_time, 'eael_pinterest_feed_token_refresh' );
    }

    public function init_pinterest_feed_token_refresh() {
        $token_expires = (int) get_option( 'eael_pinterest_token_expires', 0 );
        $refresh_token = sanitize_text_field( get_option( 'eael_pinterest_refresh_token', '' ) );

        if ( empty( $refresh_token ) || $token_expires <= 0 ) {
            return;
        }

        if ( wp_next_scheduled( 'eael_pinterest_feed_token_refresh' ) ) {
            return;
        }

        $expires_in = max( 60, $token_expires - time() );
        $this->schedule_pinterest_feed_token_refresh( $expires_in );
    }

    public function refresh_pinterest_feed_token_cron() {
        $refresh_token = sanitize_text_field( get_option( 'eael_pinterest_refresh_token', '' ) );

        if ( empty( $refresh_token ) ) {
            return;
        }

        $result = $this->eael_refresh_pinterest_access_token( $refresh_token );

        if ( ! empty( $result['access_token'] ) ) {
            $expires_in = intval( $result['expires_in'] ?? 2592000 );
            update_option( 'eael_pinterest_access_token', sanitize_text_field( $result['access_token'] ) );
            update_option( 'eael_pinterest_token_expires', time() + $expires_in );
            // Persist a rotated refresh token when the proxy returns one.
            if ( ! empty( $result['refresh_token'] ) ) {
                update_option( 'eael_pinterest_refresh_token', sanitize_text_field( $result['refresh_token'] ) );
            }
            delete_option( 'eael_pinterest_refresh_failures' );
            $this->schedule_pinterest_feed_token_refresh( $expires_in );
            return;
        }

        // 401 on refresh means the refresh_token itself was revoked — clear state and stop.
        if ( ! empty( $result['__status'] ) && 401 === (int) $result['__status'] ) {
            $this->mark_pinterest_revoked();
            return;
        }

        // Transient failure (rate-limited, 5xx, network). Cap retries to avoid loops.
        $failures = (int) get_option( 'eael_pinterest_refresh_failures', 0 ) + 1;
        update_option( 'eael_pinterest_refresh_failures', $failures );

        if ( $failures >= 3 ) {
            update_option( 'eael_pinterest_connection_status', 'error' );
            update_option( 'eael_pinterest_last_error', __( 'Pinterest token refresh failed repeatedly. Please reconnect.', 'essential-addons-elementor' ) );
            return;
        }

        wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'eael_pinterest_feed_token_refresh' );
    }

    /**
     * Refresh the access token through the EA proxy (the proxy holds the app secret and
     * talks to Pinterest's token endpoint). Returns the proxy payload with an `__status` key.
     *
     * @param string $refresh_token
     * @return array
     */
    private function eael_refresh_pinterest_access_token( $refresh_token ) {
        $response = wp_remote_post(
            $this->get_pinterest_proxy_url() . '/',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body'    => wp_json_encode( [
                    'action'        => 'refresh',
                    'refresh_token' => $refresh_token,
                    'site'          => home_url(),
                ] ),
                'timeout' => 30,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [ '__status' => 0, '__error' => $response->get_error_message() ];
        }

        $status = (int) wp_remote_retrieve_response_code( $response );
        $data   = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) ) {
            $data = [];
        }
        $data['__status'] = $status;

        return $data;
    }

    private function mark_pinterest_revoked() {
        delete_option( 'eael_pinterest_access_token' );
        delete_option( 'eael_pinterest_refresh_token' );
        delete_option( 'eael_pinterest_token_expires' );
        delete_option( 'eael_pinterest_refresh_expires' );
        delete_option( 'eael_pinterest_refresh_failures' );
        update_option( 'eael_pinterest_connection_status', 'revoked' );
        update_option( 'eael_pinterest_last_error', __( 'Pinterest revoked access. Please reconnect.', 'essential-addons-elementor' ) );

        $timestamp = wp_next_scheduled( 'eael_pinterest_feed_token_refresh' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'eael_pinterest_feed_token_refresh' );
        }
    }

    private function disconnect_pinterest_feed() {
        delete_option( 'eael_pinterest_access_token' );
        delete_option( 'eael_pinterest_refresh_token' );
        delete_option( 'eael_pinterest_token_expires' );
        delete_option( 'eael_pinterest_refresh_expires' );
        delete_option( 'eael_pinterest_user_info' );
        delete_option( 'eael_pinterest_last_error' );
        delete_option( 'eael_pinterest_connection_status' );
        delete_option( 'eael_pinterest_refresh_failures' );

        $timestamp = wp_next_scheduled( 'eael_pinterest_feed_token_refresh' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'eael_pinterest_feed_token_refresh' );
        }
    }

    /**
     * Stable cache-key identity. Prefers immutable username over the rotating access_token
     * to avoid orphaning transients on every token refresh.
     *
     * @param string $access_token
     * @return string
     */
    protected function get_pinterest_cache_identity( $access_token ) {
        $user_info = get_option( 'eael_pinterest_user_info', [] );
        if ( ! empty( $user_info['username'] ) ) {
            return 'u:' . $user_info['username'];
        }
        return 't:' . md5( $access_token );
    }

    /**
     * Returns the current access_token, refreshing inline (via the proxy) if we're inside
     * the 5-min expiry buffer. The site-wide token is the single source of truth.
     *
     * @param array $settings  Widget settings (kept for call-site compatibility; unused).
     * @return string  access_token or empty string.
     */
    protected function get_current_pinterest_access_token( $settings = [] ) {
        $access_token  = sanitize_text_field( get_option( 'eael_pinterest_access_token', '' ) );
        $token_expires = (int) get_option( 'eael_pinterest_token_expires', 0 );

        if ( ! empty( $access_token ) && $token_expires > ( time() + 300 ) ) {
            return $access_token;
        }

        $refresh_token = sanitize_text_field( get_option( 'eael_pinterest_refresh_token', '' ) );

        if ( ! empty( $refresh_token ) ) {
            $result = $this->eael_refresh_pinterest_access_token( $refresh_token );
            if ( ! empty( $result['access_token'] ) ) {
                $expires_in = intval( $result['expires_in'] ?? 2592000 );
                update_option( 'eael_pinterest_access_token', sanitize_text_field( $result['access_token'] ) );
                update_option( 'eael_pinterest_token_expires', time() + $expires_in );
                if ( ! empty( $result['refresh_token'] ) ) {
                    update_option( 'eael_pinterest_refresh_token', sanitize_text_field( $result['refresh_token'] ) );
                }
                delete_option( 'eael_pinterest_refresh_failures' );
                $this->schedule_pinterest_feed_token_refresh( $expires_in );
                return $result['access_token'];
            }

            if ( ! empty( $result['__status'] ) && 401 === (int) $result['__status'] ) {
                $this->mark_pinterest_revoked();
            }
        }

        return '';
    }

    /**
     * Derive display mode and visual type from the layout preset.
     *
     * @param string $layout  Currently registered layout id.
     * @return array  [ display_mode, visual ]
     */
    private function get_active_layout( $settings ) {
        $is_boards = ( $settings['eael_pinterest_feed_type'] ?? '' ) === 'boards';
        if ( $is_boards ) {
            return $settings['eael_pinterest_feed_layout_boards'] ?? 'layout-1';
        }
        return $settings['eael_pinterest_feed_layout'] ?? 'layout-1';
    }

    private function derive_layout_props( $layout ) {
        switch ( $layout ) {
            case 'layout-1': return [ 'grid',    'overlay'   ];
            case 'layout-2': return [ 'masonry', 'overlay'   ];
            case 'layout-4': return [ 'slider',  'card'      ];
            case 'layout-5': return [ 'slider',  'editorial' ];
            case 'layout-3': return [ 'masonry', 'polaroid'  ];
            default:         return [ 'grid',    'overlay'   ];
        }
    }

    /**
     * Fetch the user's boards as a key→label array for SELECT controls.
     *
     * @param string $access_token
     * @param int    $cache_duration  Minutes; matches eael_pinterest_feed_data_cache_limit.
     * @return array  [ board_id => 'Board Name (pin_count Pins)', ... ]
     */
    protected function get_boards_for_dropdown( $access_token, $cache_duration = 60 ) {
        if ( empty( $access_token ) ) {
            return [];
        }

        $cache_duration = max( 1, absint( $cache_duration ) );
        $identity_hash  = md5( $this->get_pinterest_cache_identity( $access_token ) );
        $cache_key      = 'eael_pf_bdd_' . $identity_hash;
        $dur_key        = 'eael_pf_bdd_dur_' . $identity_hash;

        // Invalidate cached payload when "Data Cache Time" changes; otherwise prior TTL persists.
        $prev_dur = get_transient( $dur_key );
        if ( $prev_dur !== false && (int) $prev_dur !== $cache_duration ) {
            delete_transient( $cache_key );
        }

        $cached = get_transient( $cache_key );
        if ( $cached !== false ) {
            return json_decode( $cached, true );
        }

        $result = $this->fetch_pinterest_boards( $access_token, 25 );
        if ( empty( $result['items'] ) ) {
            return [];
        }

        $options = [];
        foreach ( $result['items'] as $board ) {
            $id        = $board['id'] ?? '';
            $name      = $board['name'] ?? $id;
            $pin_count = absint( $board['pin_count'] ?? 0 );
            if ( ! empty( $id ) ) {
                $options[ $id ] = $name . ' (' . $pin_count . ' Pins)';
            }
        }

        $ttl = $cache_duration * MINUTE_IN_SECONDS;
        set_transient( $cache_key, wp_json_encode( $options ), $ttl );
        set_transient( $dur_key, $cache_duration, $ttl );

        return $options;
    }


    /**
     * Main entry point: handles both normal widget render and AJAX load-more.
     *
     *
     * @param array $settings Optional; used when called from render().
     * @return string|void  HTML string on normal render; sends JSON and exits on AJAX.
     */
    public function pinterest_feed_load_more( $settings = [] ) {
        $is_ajax = isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'pinterest_feed_load_more';

        if ( $is_ajax ) {
            check_ajax_referer( 'essential-addons-elementor', 'security' );

            $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 0;

            if ( empty( $_POST['post_id'] ) ) {
                wp_send_json_error( __( 'Post ID is missing', 'essential-addons-elementor' ) );
            }
            $post_id = absint( $_POST['post_id'] );

            if ( empty( $_POST['widget_id'] ) ) {
                wp_send_json_error( __( 'Widget ID is missing', 'essential-addons-elementor' ) );
            }
            $widget_id = sanitize_text_field( wp_unslash( $_POST['widget_id'] ) );

            $settings = HelperClass::eael_get_widget_settings( $post_id, $widget_id );

            if ( ! empty( $_POST['settings'] ) ) {
                if ( ! current_user_can( 'edit_posts' ) ) {
                    wp_send_json_error( __( 'Unauthorized', 'essential-addons-elementor' ) );
                }

                parse_str( wp_unslash( $_POST['settings'] ), $new_settings );

                $allowed_keys = [
                    'eael_pinterest_feed_type',
                    'eael_pinterest_feed_board_id',
                    'eael_pinterest_feed_image_count',
                    'eael_pinterest_feed_board_count',
                    'eael_pinterest_feed_layout',
                    'eael_pinterest_feed_layout_boards',
                    'eael_pinterest_feed_overlay_mode',
                    'eael_pinterest_feed_show_caption',
                    'eael_pinterest_feed_show_date',
                    'eael_pinterest_feed_show_description',
                    'eael_pinterest_feed_show_read_more',
                    'eael_pinterest_feed_description_length',
                    'eael_pinterest_feed_read_more_text',
                    'eael_pinterest_feed_data_cache_limit',
                    'eael_pinterest_feed_board_cover_style',
                    'eael_pinterest_feed_show_board_name',
                    'eael_pinterest_feed_show_pin_count',
                    'eael_pinterest_feed_modern_show_board',
                    'eael_pinterest_feed_modern_show_domain',
                    'eael_pinterest_feed_modern_description_lines',
                    'eael_pinterest_feed_modern_show_credit',
                    'eael_pinterest_feed_editorial_show_url',
                    'eael_pinterest_feed_overlay_show_board',
                    'eael_pinterest_feed_overlay_show_domain',
                    'eael_pinterest_feed_polaroid_always_tilt',
                    'eael_pinterest_feed_card_show_chip',
                    'eael_pinterest_feed_featured_show_board',
                    'eael_pinterest_feed_featured_show_date',
                    'eael_pinterest_feed_featured_show_read_more',
                    'eael_pinterest_feed_featured_read_more_text',
                ];
                $new_settings = array_intersect_key( $new_settings, array_flip( $allowed_keys ) );

                $settings = wp_parse_args( $new_settings, $settings );
            }

        } else {
            $page = 0;
            if ( empty( $settings ) ) {
                $settings = $this->get_settings_for_display();
            }
        }

        $access_token = $this->get_current_pinterest_access_token( $settings );
        if ( empty( $access_token ) ) {
            if ( $is_ajax ) {
                wp_send_json_error( __( 'Pinterest is not connected. Connect from EA Dashboard.', 'essential-addons-elementor' ) );
            }
            return '';
        }

        $feed_type      = $settings['eael_pinterest_feed_type']           ?? 'user_pins';
        $board_id       = $settings['eael_pinterest_feed_board_id']       ?? '';
        $cache_duration = absint( $settings['eael_pinterest_feed_data_cache_limit'] ?? 60 );
        $is_boards      = ( $feed_type === 'boards' );
        $image_count    = $is_boards
            ? max( 1, absint( $settings['eael_pinterest_feed_board_count'] ?? 12 ) )
            : max( 1, absint( $settings['eael_pinterest_feed_image_count'] ?? 12 ) );

        // Identity hash uses username (stable across token rotations) to avoid orphaned transients.
        $cache_identity = $this->get_pinterest_cache_identity( $access_token );
        $feed_hash = md5( $cache_identity . $feed_type . $board_id );
        $cache_key = 'eael_pf3_' . $feed_hash;
        $dur_key   = 'eael_pf3_dur_' . $feed_hash;

        $prev_dur = get_transient( $dur_key );
        if ( $prev_dur !== false && (int) $prev_dur !== $cache_duration ) {
            delete_transient( $cache_key );
        }

        $ttl = $cache_duration * MINUTE_IN_SECONDS;

        $pinterest_data = [];

        if ( $is_ajax && ( $cached = get_transient( $cache_key ) ) ) {
            $pinterest_data = json_decode( $cached, true );

            if ( ( $page * $image_count >= count( $pinterest_data['items'] ?? [] ) )
                && ! empty( $pinterest_data['bookmark'] ) ) {

                $more = $is_boards
                    ? $this->fetch_pinterest_boards( $access_token, 25, $pinterest_data['bookmark'] )
                    : $this->fetch_pinterest_data( $access_token, $feed_type, $board_id, 100, $pinterest_data['bookmark'] );

                if ( ! empty( $more['items'] ) ) {
                    $pinterest_data['items']    = array_merge( $pinterest_data['items'], $more['items'] );
                    $pinterest_data['bookmark'] = $more['bookmark'] ?? '';
                    set_transient( $cache_key, wp_json_encode( $pinterest_data ), $ttl );
                    set_transient( $dur_key, $cache_duration, $ttl );
                }
            }
        }

        if ( get_transient( $cache_key ) === false ) {
            $result = $is_boards
                ? $this->fetch_pinterest_boards( $access_token, 25 )
                : $this->fetch_pinterest_data( $access_token, $feed_type, $board_id, 100 );

            if ( ! empty( $result['items'] ) ) {
                $pinterest_data = $result;
                set_transient( $cache_key, wp_json_encode( $pinterest_data ), $ttl );
                set_transient( $dur_key, $cache_duration, $ttl );
            }
        } elseif ( empty( $pinterest_data ) ) {
            $pinterest_data = json_decode( get_transient( $cache_key ), true );
        }

        if ( empty( $pinterest_data['items'] ) ) {
            if ( $is_ajax ) {
                wp_send_json( [ 'html' => '', 'num_pages' => 0 ] );
            }
            return '';
        }

        $all_items  = $pinterest_data['items'];
        $page_items = array_slice( $all_items, $page * $image_count, $image_count );

        $html = '';
        if ( ! empty( $page_items ) ) {
            $html = $is_boards
                ? $this->render_board_items( $page_items, $settings )
                : $this->render_pinterest_items( $page_items, $settings );
        }

        $has_more = ! empty( $pinterest_data['bookmark'] )
            || count( $all_items ) > ( $page + 1 ) * $image_count;

        if ( $is_ajax ) {
            wp_send_json( [
                'html'      => $html,
                'num_pages' => $has_more ? $page + 1 : $page,
            ] );
        }

        $this->eael_pf_has_more = $has_more;

        return $html;
    }

    /**
     * Fetch a batch of pins from Pinterest API v5.
     *
     * @param string $access_token
     * @param string $feed_type   'user_pins' | 'board_pins'
     * @param string $board_id
     * @param int    $count       Pins per request (page_size).
     * @param string $bookmark    Cursor returned by a previous response; empty for first page.
     * @return array|false  ['items' => [...], 'bookmark' => '...'] or false on failure.
     */
    protected function fetch_pinterest_data( $access_token, $feed_type, $board_id, $count, $bookmark = '' ) {
        $base      = 'https://api.pinterest.com/v5/';
        $fields    = 'id,title,description,note,link,created_at,alt_text,board_id,media';
        $endpoint  = ( $feed_type === 'board_pins' && ! empty( $board_id ) )
            ? 'boards/' . rawurlencode( $board_id ) . '/pins'
            : 'pins';

        $page_size = min( 100, max( 25, absint( $count ) ) );

        $params = [
            'page_size'  => $page_size,
            'pin_fields' => $fields,
        ];
        if ( ! empty( $bookmark ) ) {
            $params['bookmark'] = $bookmark;
        }

        $response = wp_remote_get(
            $base . $endpoint . '?' . http_build_query( $params ),
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 30,
            ]
        );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['items'] ) || ! is_array( $data['items'] ) ) {
            return false;
        }

        $bookmark_out = $data['bookmark'] ?? '';
        if ( count( $data['items'] ) < $page_size ) {
            $bookmark_out = '';
        }

        return [
            'items'    => $data['items'],
            'bookmark' => $bookmark_out,
        ];
    }

    /**
     * Build the combined HTML for an array of pin objects.
     *
     * @param array $pins
     * @param array $settings
     * @return string
     */
    protected function render_pinterest_items( $pins, $settings ) {
        $layout = $this->get_active_layout( $settings );
        [ $display_mode, $visual ] = $this->derive_layout_props( $layout );
        $is_slider = ( $display_mode === 'slider' );
        $html      = '';

        foreach ( $pins as $pin ) {
            $image_url = $this->get_pin_image( $pin );
            if ( empty( $image_url ) ) {
                continue;
            }

            $item_class = 'eael-pinterest-feed-item';
            if ( $is_slider ) {
                $item_class .= ' swiper-slide';
            }

            $html .= '<div class="' . $item_class . '">';
            $html .= '<div class="eael-pinterest-feed-item-inner">';
            if ( $visual === 'card' ) {
                $html .= $this->render_card_layout( $pin, $image_url, $settings );
            } elseif ( $visual === 'editorial' ) {
                $html .= $this->render_editorial_slider_layout( $pin, $image_url, $settings );
            } elseif ( $visual === 'polaroid' ) {
                $html .= $this->render_polaroid_layout( $pin, $image_url, $settings );
            } else {
                $html .= $this->render_overlay_layout( $pin, $image_url, $settings );
            }
            $html .= '</div></div>';
        }

        return $html;
    }

    /**
     * Extract the best available image URL from a pin's media field.
     *
     *
     * @param array $pin
     * @return string  Sanitised URL or empty string if unavailable.
     */
    protected function get_pin_image( $pin ) {
        $images = $pin['media']['images'] ?? [];
        if ( empty( $images ) ) {
            return '';
        }

        foreach ( [ 'originals', '1200x', '600x', '400x300', '150x150' ] as $size ) {
            if ( ! empty( $images[ $size ]['url'] ) ) {
                return esc_url_raw( $images[ $size ]['url'] );
            }
        }

        return '';
    }

    /**
     * Shared helper: build a pin's link and common text fields.
     *
     * @param array $pin
     * @return array  [title, link, created_at, description]
     */
    private function parse_pin( $pin ) {
        $title       = $pin['title'] ?? $pin['note'] ?? $pin['alt_text'] ?? '';
        $description = $pin['description'] ?? $pin['note'] ?? '';
        $link        = ! empty( $pin['link'] )
            ? $pin['link']
            : 'https://pinterest.com/pin/' . ( $pin['id'] ?? '' ) . '/';

        return [ $title, $link, $pin['created_at'] ?? '', $description ];
    }

    /**
     * Overlay layout: image with a caption (used for grid and masonry).
     *
     *
     * @param array  $pin
     * @param string $image_url
     * @param array  $settings
     * @return string
     */
    protected function render_overlay_layout( $pin, $image_url, $settings ) {
        [ $title, $link ] = $this->parse_pin( $pin );

        $show_caption = ( $settings['eael_pinterest_feed_show_caption']       ?? 'yes' ) === 'yes';
        $show_board   = ( $settings['eael_pinterest_feed_overlay_show_board'] ?? 'yes' ) === 'yes';
        $overlay_mode = $settings['eael_pinterest_feed_overlay_mode']         ?? 'always';
        $always_on    = ( $overlay_mode === 'always' );

        $domain_mode = $settings['eael_pinterest_feed_overlay_show_domain'] ?? 'hover';
        if ( $domain_mode === 'yes' ) {
            $domain_mode = 'hover';
        }
        $domain_hover  = ( $domain_mode === 'hover' );
        $domain_inline = ( $domain_mode === 'always' );

        $board_name = '';
        if ( $show_board && ! empty( $pin['board_id'] ) ) {
            $token = $this->get_current_pinterest_access_token( $settings );
            if ( ! empty( $token ) ) {
                $cache_dur  = max( 1, absint( $settings['eael_pinterest_feed_data_cache_limit'] ?? 60 ) );
                $boards_map = $this->get_pinterest_boards_map( $token, $cache_dur );
                $board_name = $boards_map[ $pin['board_id'] ] ?? '';
            }
        }

        // Computed once for either the hover pill or inline meta; pinterest.com fallbacks are skipped.
        $domain    = '';
        $want_dom  = ( $domain_hover || $domain_inline );
        if ( $want_dom && ! empty( $pin['link'] ) ) {
            $host = wp_parse_url( $pin['link'], PHP_URL_HOST );
            if ( ! empty( $host ) && stripos( $host, 'pinterest.' ) === false ) {
                $domain = preg_replace( '/^www\./i', '', $host );
            }
        }

        $aria = ! empty( $title ) ? $title : __( 'Open pin on Pinterest', 'essential-addons-elementor' );

        $html  = '<a href="' . esc_url( $link ) . '" target="_blank" rel="nofollow noopener" aria-label="' . eael_neutralize_shortcodes( esc_attr( $aria ) ) . '">';
        $html .= '<img class="eael-pinterest-feed-img" src="' . esc_url( $image_url ) . '" alt="' . eael_neutralize_shortcodes( esc_attr( $title ) ) . '" loading="lazy">';

        if ( $domain_hover && ! empty( $domain ) ) {
            $html .= '<span class="eael-pinterest-feed-domain">'
                   . '<i class="fas fa-link" aria-hidden="true"></i> '
                   . eael_neutralize_shortcodes( esc_html( $domain ) )
                   . '</span>';
        }

        $has_meta = ( $show_board     && ! empty( $board_name ) )
                 || ( $domain_inline  && ! empty( $domain ) );

        if ( ( $show_caption && ! empty( $title ) ) || $has_meta ) {
            $caption_class = 'eael-pinterest-feed-caption';
            if ( ! $always_on ) {
                $caption_class .= ' eael-pinterest-feed-caption-hover';
            }
            $html .= '<div class="' . esc_attr( $caption_class ) . '">';
            $html .= '<div class="eael-pinterest-feed-caption-inner">';

            if ( $show_caption && ! empty( $title ) ) {
                $html .= '<div class="eael-pinterest-feed-caption-text">' . eael_neutralize_shortcodes( esc_html( $title ) ) . '</div>';
            }

            if ( $has_meta ) {
                $html .= '<div class="eael-pinterest-feed-meta">';

                if ( $show_board && ! empty( $board_name ) ) {
                    $html .= '<span class="eael-pinterest-feed-board">'
                           . '<i class="fas fa-folder" aria-hidden="true"></i> '
                           . eael_neutralize_shortcodes( esc_html( $board_name ) )
                           . '</span>';
                }
                if ( $domain_inline && ! empty( $domain ) ) {
                    $html .= '<span class="eael-pinterest-feed-domain-inline">'
                           . '<i class="fas fa-link" aria-hidden="true"></i> '
                           . eael_neutralize_shortcodes( esc_html( $domain ) )
                           . '</span>';
                }

                $html .= '</div>';
            }

            $html .= '</div></div>';
        }

        $html .= '</a>';
        return $html;
    }

    /**
     * Card layout: header / image / footer card presentation.
     *
     * @param array  $pin
     * @param string $image_url
     * @param array  $settings
     * @return string
     */
    protected function render_card_layout( $pin, $image_url, $settings ) {
        [ $title, $link, $created_at, $description ] = $this->parse_pin( $pin );

        $show_caption     = ( $settings['eael_pinterest_feed_show_caption'] ?? 'yes' ) === 'yes';
        $show_date        = ( $settings['eael_pinterest_feed_show_date']    ?? '' )    === 'yes';
        $show_description = ( $settings['eael_pinterest_feed_show_description'] ?? 'yes' ) === 'yes';
        $show_read_more   = ( $settings['eael_pinterest_feed_show_read_more']   ?? 'yes' ) === 'yes';
        $show_chip        = ( $settings['eael_pinterest_feed_card_show_chip']   ?? 'yes' ) === 'yes';
        $desc_length      = absint( $settings['eael_pinterest_feed_description_length'] ?? 60 );
        $read_more_text   = $settings['eael_pinterest_feed_read_more_text'] ?? esc_html__( 'Read More', 'essential-addons-elementor' );

        $board_name = '';
        if ( $show_chip && ! empty( $pin['board_id'] ) ) {
            $token = $this->get_current_pinterest_access_token( $settings );
            if ( ! empty( $token ) ) {
                $cache_dur  = max( 1, absint( $settings['eael_pinterest_feed_data_cache_limit'] ?? 60 ) );
                $boards_map = $this->get_pinterest_boards_map( $token, $cache_dur );
                $board_name = $boards_map[ $pin['board_id'] ] ?? '';
            }
        }

        $html  = '<a class="eael-pf-card-link" href="' . esc_url( $link ) . '" target="_blank" rel="nofollow noopener">';

        $html .= '<div class="eael-pf-card-image">';
        $html .= '<img class="eael-pinterest-feed-img" src="' . esc_url( $image_url ) . '" alt="' . eael_neutralize_shortcodes( esc_attr( $title ) ) . '" loading="lazy">';
        $html .= '</div>';

        // Only emit the card body when something will actually render inside it —
        $has_body = ( $show_caption ) || ( $show_description )
                || ( $show_date        && ! empty( $created_at ) )
                || $show_read_more
                || ( $show_chip        && ! empty( $board_name ) );

        if ( $has_body ) {
            $html .= '<div class="eael-pf-card-body">';

            // Chip is absolute-positioned inside .eael-pf-card-body so the image's overflow:hidden doesn't clip it.
            if ( $show_chip && ! empty( $board_name ) ) {
                $html .= '<div class="eael-pf-card-chip">'
                    . '<i class="fas fa-folder" aria-hidden="true"></i>'
                    . '<span>' . eael_neutralize_shortcodes( esc_html( $board_name ) ) . '</span>'
                    . '</div>';
            }

            if ( $show_caption && ! empty( $title ) ) {
                $html .= '<h3 class="eael-pf-card-title">' . eael_neutralize_shortcodes( esc_html( $title ) ) . '</h3>';
            }

            if ( $show_date && ! empty( $created_at ) ) {
                $html .= '<div class="eael-pf-card-date">'
                    . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $created_at ) ) )
                    . '</div>';
            }

            if ( $show_description && ! empty( $description ) ) {
                $desc_text = $desc_length > 0 && mb_strlen( $description ) > $desc_length
                    ? mb_substr( $description, 0, $desc_length ) . '...'
                    : $description;
                $html .= '<div class="eael-pf-card-description">' . eael_neutralize_shortcodes( esc_html( $desc_text ) ) . '</div>';
            }

            if ( $show_read_more ) {
                $html .= '<div class="eael-pf-card-meta">';
                $html .= '<span class="eael-pf-card-read-more">'
                    . esc_html( $read_more_text )
                    . ' <i class="fas fa-chevron-right" aria-hidden="true"></i></span>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</a>';

        return $html;
    }

    /**
     * Build a board_id → board_name map for the connected account.
     * @param string $access_token
     * @param int    $cache_duration  Minutes; matches eael_pinterest_feed_data_cache_limit.
     * @return array  [ board_id => 'Board Name', ... ]
     */
    protected function get_pinterest_boards_map( $access_token, $cache_duration = 60 ) {
        if ( empty( $access_token ) ) {
            return [];
        }

        $cache_duration = max( 1, absint( $cache_duration ) );
        $identity_hash  = md5( $this->get_pinterest_cache_identity( $access_token ) );
        $cache_key      = 'eael_pf_bmap_' . $identity_hash;

        $cached = get_transient( $cache_key );
        if ( $cached !== false ) {
            return json_decode( $cached, true );
        }

        $result = $this->fetch_pinterest_boards( $access_token, 25 );
        if ( empty( $result['items'] ) ) {
            return [];
        }

        $map = [];
        foreach ( $result['items'] as $board ) {
            $id   = $board['id']   ?? '';
            $name = $board['name'] ?? '';
            if ( ! empty( $id ) && ! empty( $name ) ) {
                $map[ $id ] = $name;
            }
        }

        set_transient( $cache_key, wp_json_encode( $map ), $cache_duration * MINUTE_IN_SECONDS );
        return $map;
    }

    /**
     * Editorial slider layout (layout-5): image on the left, content stack
     * on the right. Magazine feel — Pinterest icon, title, date, description,
     * source URL, and a "Read More" pill button.
     *
     * @param array  $pin
     * @param string $image_url
     * @param array  $settings
     * @return string
     */
    protected function render_editorial_slider_layout( $pin, $image_url, $settings ) {
        [ $title, $link, $created_at, $description ] = $this->parse_pin( $pin );

        $show_caption     = ( $settings['eael_pinterest_feed_show_caption']                ?? 'yes' ) === 'yes';
        $show_board       = ( $settings['eael_pinterest_feed_featured_show_board']         ?? 'yes' ) === 'yes';
        $show_date        = ( $settings['eael_pinterest_feed_featured_show_date']          ?? 'yes' ) === 'yes';
        $show_description = ( $settings['eael_pinterest_feed_show_description']            ?? 'yes' ) === 'yes';
        $show_url         = ( $settings['eael_pinterest_feed_editorial_show_url']          ?? 'yes' ) === 'yes';
        $show_read_more   = ( $settings['eael_pinterest_feed_featured_show_read_more']     ?? 'yes' ) === 'yes';
        $desc_length      = absint( $settings['eael_pinterest_feed_description_length'] ?? 0 );
        $read_more_text   = $settings['eael_pinterest_feed_featured_read_more_text'] ?? esc_html__( 'Read More', 'essential-addons-elementor' );

        $board_name = '';
        if ( $show_board && ! empty( $pin['board_id'] ) ) {
            $token = $this->get_current_pinterest_access_token( $settings );
            if ( ! empty( $token ) ) {
                $cache_dur  = max( 1, absint( $settings['eael_pinterest_feed_data_cache_limit'] ?? 60 ) );
                $boards_map = $this->get_pinterest_boards_map( $token, $cache_dur );
                $board_name = $boards_map[ $pin['board_id'] ] ?? '';
            }
        }

        if ( $show_description && $desc_length > 0 && mb_strlen( $description ) > $desc_length ) {
            $description = mb_substr( $description, 0, $desc_length ) . '...';
        }

        $source_url = '';
        if ( $show_url && ! empty( $pin['link'] ) ) {
            $host = wp_parse_url( $pin['link'], PHP_URL_HOST );
            if ( ! empty( $host ) && stripos( $host, 'pinterest.' ) === false ) {
                $source_url = $pin['link'];
            }
        }

        $aria = ! empty( $title ) ? $title : __( 'Open pin on Pinterest', 'essential-addons-elementor' );

        $html  = '<a class="eael-pf-editorial-link" href="' . esc_url( $link ) . '" target="_blank" rel="nofollow noopener" aria-label="' . eael_neutralize_shortcodes( esc_attr( $aria ) ) . '">';
        $html .= '<div class="eael-pf-editorial-image">';
        $html .= '<img class="eael-pinterest-feed-img" src="' . esc_url( $image_url ) . '" alt="' . eael_neutralize_shortcodes( esc_attr( $title ) ) . '" loading="lazy">';
        $html .= '</div>';

        $html .= '<div class="eael-pf-editorial-body">';

        if ( $show_board && ! empty( $board_name ) ) {
            $html .= '<span class="eael-pf-editorial-board">' . eael_neutralize_shortcodes( esc_html( $board_name ) ) . '</span>';
        }

        if ( $show_caption && ! empty( $title ) ) {
            $html .= '<h3 class="eael-pf-editorial-title">' . eael_neutralize_shortcodes( esc_html( $title ) ) . '</h3>';
        }

        if ( $show_date && ! empty( $created_at ) ) {
            $html .= '<div class="eael-pf-editorial-date">'
                   . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $created_at ) ) )
                   . '</div>';
        }

        if ( $show_description && ! empty( $description ) ) {
            $html .= '<p class="eael-pf-editorial-description">' . eael_neutralize_shortcodes( esc_html( $description ) ) . '</p>';
        }

        if ( ! empty( $source_url ) ) {
            $html .= '<div class="eael-pf-editorial-url">' . eael_neutralize_shortcodes( esc_html( $source_url ) ) . '</div>';
        }

        if ( $show_read_more ) {
            $html .= '<span class="eael-pf-editorial-button">'
                   . esc_html( $read_more_text )
                   . ' <i class="fas fa-chevron-right" aria-hidden="true"></i>'
                   . '</span>';
        }

        $html .= '</div>'; // .eael-pf-editorial-body
        $html .= '</a>';

        return $html;
    }

    /**
     * Polaroid layout (layout-3): white-framed image with serif caption below.
     * Scrapbook / mood-board feel; meta is intentionally minimal.
     *
     * @param array  $pin
     * @param string $image_url
     * @param array  $settings
     * @return string
     */
    protected function render_polaroid_layout( $pin, $image_url, $settings ) {
        [ $title, $link ] = $this->parse_pin( $pin );

        $show_caption = ( $settings['eael_pinterest_feed_show_caption']         ?? 'yes' ) === 'yes';
        $show_board   = ( $settings['eael_pinterest_feed_modern_show_board']    ?? 'yes' ) === 'yes';
        $show_domain  = ( $settings['eael_pinterest_feed_modern_show_domain']   ?? 'yes' ) === 'yes';
        $title_lines  = max( 1, min( 3, absint( $settings['eael_pinterest_feed_modern_description_lines'] ?? 2 ) ) );

        $board_name = '';
        if ( $show_board && ! empty( $pin['board_id'] ) ) {
            $token = $this->get_current_pinterest_access_token( $settings );
            if ( ! empty( $token ) ) {
                $cache_dur  = max( 1, absint( $settings['eael_pinterest_feed_data_cache_limit'] ?? 60 ) );
                $boards_map = $this->get_pinterest_boards_map( $token, $cache_dur );
                $board_name = $boards_map[ $pin['board_id'] ] ?? '';
            }
        }

        $domain = '';
        if ( $show_domain && ! empty( $pin['link'] ) ) {
            $host = wp_parse_url( $pin['link'], PHP_URL_HOST );
            if ( ! empty( $host ) && stripos( $host, 'pinterest.' ) === false ) {
                $domain = preg_replace( '/^www\./i', '', $host );
            }
        }

        $aria = ! empty( $title ) ? $title : __( 'Open pin on Pinterest', 'essential-addons-elementor' );

        $html  = '<a class="eael-pf-polaroid-link" href="' . esc_url( $link ) . '" target="_blank" rel="nofollow noopener" aria-label="' . eael_neutralize_shortcodes( esc_attr( $aria ) ) . '">';
        $html .= '<div class="eael-pf-polaroid-frame">';
        $html .= '<div class="eael-pf-polaroid-image">';
        $html .= '<img class="eael-pinterest-feed-img" src="' . esc_url( $image_url ) . '" alt="' . eael_neutralize_shortcodes( esc_attr( $title ) ) . '" loading="lazy">';
        $html .= '</div>';

        $html .= '<div class="eael-pf-polaroid-caption">';
        if ( $show_caption && ! empty( $title ) ) {
            $html .= '<h3 class="eael-pf-polaroid-title" style="--eael-pf-title-lines: ' . esc_attr( $title_lines ) . ';">'
                   . eael_neutralize_shortcodes( esc_html( $title ) )
                   . '</h3>';
        }

        if ( ( $show_board && ! empty( $board_name ) ) || ! empty( $domain ) ) {
            $html .= '<div class="eael-pf-polaroid-meta">';
            if ( $show_board && ! empty( $board_name ) ) {
                $html .= '<span class="eael-pf-polaroid-board">' . eael_neutralize_shortcodes( esc_html( $board_name ) ) . '</span>';
            }
            if ( ! empty( $domain ) ) {
                $html .= '<span class="eael-pf-polaroid-domain">' . eael_neutralize_shortcodes( esc_html( $domain ) ) . '</span>';
            }
            $html .= '</div>';
        }

        $html .= '</div>'; // .eael-pf-polaroid-caption
        $html .= '</div>'; // .eael-pf-polaroid-frame
        $html .= '</a>';

        return $html;
    }

    /**
     * Fetch current user's account info from Pinterest API v5.
     *
     * @param string $access_token
     * @param int    $cache_duration  Cache TTL in minutes.
     * @return array|false  User data or false on failure.
     */
    protected function fetch_pinterest_user_info( $access_token, $cache_duration = 60 ) {
        $cache_key = 'eael_pf_user_' . md5( $this->get_pinterest_cache_identity( $access_token ) );

        // OAuth callback passes $cache_duration=0 to force a fresh fetch for the new connection.
        if ( $cache_duration > 0 ) {
            $cached = get_transient( $cache_key );
            if ( $cached !== false ) {
                return json_decode( $cached, true );
            }
        }

        $response = wp_remote_get(
            'https://api.pinterest.com/v5/user_account',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data ) || ! is_array( $data ) ) {
            return false;
        }

        if ( $cache_duration > 0 ) {
            set_transient( $cache_key, wp_json_encode( $data ), $cache_duration * MINUTE_IN_SECONDS );
        }

        return $data;
    }

    /**
     * Render the profile header section above the feed.
     *
     * @param array $settings  Widget settings.
     * @return string  HTML or empty string.
     */
    protected function render_profile_header( $settings ) {
        if ( ( $settings['eael_pinterest_feed_show_header'] ?? '' ) !== 'yes' ) {
            return '';
        }

        $access_token   = $this->get_current_pinterest_access_token( $settings );
        $cache_duration = absint( $settings['eael_pinterest_feed_data_cache_limit'] ?? 60 );

        if ( empty( $access_token ) ) {
            return '';
        }

        $user_info = $this->fetch_pinterest_user_info( $access_token, $cache_duration );
        if ( empty( $user_info ) ) {
            return '';
        }

        // Migrate legacy numeric style values to the inline/stacked system.
        $raw_style    = $settings['eael_pinterest_feed_header_style'] ?? 'inline';
        $header_style = in_array( $raw_style, [ 'inline', 'stacked' ], true )
            ? $raw_style
            : ( $raw_style === '4' ? 'stacked' : 'inline' );

        $meta_order        = $settings['eael_pinterest_feed_header_meta_order'] ?? 'stats-first';
        $show_follow       = ( $settings['eael_pinterest_feed_show_follow_btn'] ?? 'yes' ) === 'yes';
        $follow_text       = $settings['eael_pinterest_feed_follow_btn_text']  ?? esc_html__( 'Follow', 'essential-addons-elementor' );
        $show_stats        = ( $settings['eael_pinterest_feed_show_stats']    ?? 'yes' ) === 'yes';
        $pinterest_url_raw = $settings['eael_pinterest_feed_pinterest_url']    ?? '';
        $pinterest_url     = is_array( $pinterest_url_raw ) ? ( $pinterest_url_raw['url'] ?? '' ) : $pinterest_url_raw;

        $avatar_url = '';
        if ( ! empty( $settings['eael_pinterest_feed_custom_avatar']['url'] ) ) {
            $avatar_url = $settings['eael_pinterest_feed_custom_avatar']['url'];
        } elseif ( ! empty( $user_info['profile_image'] ) ) {
            $avatar_url = $user_info['profile_image'];
        }

        $username       = $user_info['username']        ?? '';
        $follower_count = absint( $user_info['follower_count']  ?? 0 );
        $following_count = absint( $user_info['following_count'] ?? 0 );

        if ( empty( $pinterest_url ) && ! empty( $username ) ) {
            $pinterest_url = 'https://www.pinterest.com/' . $username . '/';
        }

        $wrap_classes = [
            'eael-pinterest-feed-profile-header',
            'eael-pf-header-style-' . $header_style,
            'eael-pf-header-meta-' . $meta_order,
        ];

        $html  = '<div class="' . esc_attr( implode( ' ', $wrap_classes ) ) . '">';
        $html .= '<div class="eael-pf-header-inner">';

        $html .= '<div class="eael-pf-header-user">';
        if ( ! empty( $avatar_url ) ) {
            $html .= '<img class="eael-pf-avatar" src="' . esc_url( $avatar_url ) . '" alt="' . eael_neutralize_shortcodes( esc_attr( $username ) ) . '" loading="lazy">';
        }
        if ( ! empty( $username ) ) {
            $html .= '<span class="eael-pf-header-username">' . eael_neutralize_shortcodes( esc_html( $username ) ) . '</span>';
        }
        $html .= '</div>';

        $stats_html = '';
        if ( $show_stats ) {
            $stats_html .= '<div class="eael-pf-stats">';
            $stats_html .= '<span class="eael-pf-stat">'
                        . esc_html( number_format_i18n( $following_count ) ) . ' '
                        . esc_html__( 'Following', 'essential-addons-elementor' )
                        . '</span>';
            $stats_html .= '<span class="eael-pf-stat">'
                        . esc_html( number_format_i18n( $follower_count ) ) . ' '
                        . esc_html__( 'Followers', 'essential-addons-elementor' )
                        . '</span>';
            $stats_html .= '</div>';
        }

        $button_html = '';
        if ( $show_follow && ! empty( $pinterest_url ) ) {
            $show_follow_icon = ( $settings['eael_pinterest_feed_show_follow_btn_pinterest_icon'] ?? 'yes' ) === 'yes';
            $button_html .= '<a href="' . esc_url( $pinterest_url ) . '" target="_blank" rel="nofollow noopener" class="eael-pf-follow-btn">'
                         . ( $show_follow_icon ? '<i class="fab fa-pinterest eael-pf-follow-icon" aria-hidden="true"></i>' : '' )
                         . '<span class="eael-pf-follow-label">' . esc_html( $follow_text ) . '</span>'
                         . '</a>';
        }

        if ( $stats_html !== '' || $button_html !== '' ) {
            $html .= '<div class="eael-pf-header-meta">';
            $html .= $meta_order === 'button-first'
                ? $button_html . $stats_html
                : $stats_html . $button_html;
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Fetch the user's boards from Pinterest API v5.
     *
     * @param string $access_token
     * @param int    $count       Number of boards to fetch.
     * @param string $bookmark    Pagination cursor.
     * @return array|false  ['items' => [...], 'bookmark' => '...'] or false.
     */
    protected function fetch_pinterest_boards( $access_token, $count = 25, $bookmark = '' ) {
        $page_size = min( 25, max( 10, absint( $count ) ) );

        $params = [ 'page_size' => $page_size ];
        if ( ! empty( $bookmark ) ) {
            $params['bookmark'] = $bookmark;
        }

        $response = wp_remote_get(
            'https://api.pinterest.com/v5/boards?' . http_build_query( $params ),
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 30,
            ]
        );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['items'] ) || ! is_array( $data['items'] ) ) {
            return false;
        }

        $bookmark_out = $data['bookmark'] ?? '';
        if ( count( $data['items'] ) < $page_size ) {
            $bookmark_out = '';
        }

        return [
            'items'    => $data['items'],
            'bookmark' => $bookmark_out,
        ];
    }

    /**
     * Fetch cover pin images for a single board.
     *
     * @param string $access_token
     * @param string $board_id
     * @param int    $count  Number of pins for the cover grid.
     * @return array  Array of image URLs.
     */
    protected function fetch_board_cover_pins( $access_token, $board_id, $count = 6 ) {
        $cache_key = 'eael_pf_bcover_' . md5( $this->get_pinterest_cache_identity( $access_token ) . $board_id );
        $cached    = get_transient( $cache_key );

        if ( $cached !== false ) {
            return json_decode( $cached, true );
        }

        $result = $this->fetch_pinterest_data( $access_token, 'board_pins', $board_id, $count );
        $images = [];

        if ( ! empty( $result['items'] ) ) {
            foreach ( $result['items'] as $pin ) {
                $url = $this->get_pin_image( $pin );
                if ( ! empty( $url ) ) {
                    $images[] = $url;
                }
                if ( count( $images ) >= $count ) {
                    break;
                }
            }
        }

        set_transient( $cache_key, wp_json_encode( $images ), 6 * HOUR_IN_SECONDS );

        return $images;
    }

    /**
     * Render a single board card for the board feed.
     *
     * @param array  $board     Board data from API.
     * @param array  $covers    Array of cover image URLs.
     * @param array  $settings  Widget settings.
     * @return string
     */
    protected function render_board_item( $board, $covers, $settings ) {
        $board_name  = $board['name'] ?? '';
        $pin_count   = absint( $board['pin_count'] ?? 0 );
        $board_id    = $board['id'] ?? '';
        $board_url   = 'https://www.pinterest.com/pin/' . $board_id . '/';

        $show_name      = ( $settings['eael_pinterest_feed_show_board_name']  ?? 'yes' ) === 'yes';
        $show_pin_count = ( $settings['eael_pinterest_feed_show_pin_count']   ?? 'yes' ) === 'yes';
        $cover_style    = $settings['eael_pinterest_feed_board_cover_style']  ?? 'mosaic-3';
        [ $display_mode, ] = $this->derive_layout_props( $this->get_active_layout( $settings ) );
        $is_slider      = ( $display_mode === 'slider' );

        $cover_counts = [
            'single-image' => 1,
            'split-2'      => 2,
            'mosaic-3'     => 3,
            'grid-4'       => 4,
            'multi-image'  => 6,
        ];
        $max = $cover_counts[ $cover_style ] ?? 6;

        $item_class = 'eael-pinterest-feed-item';
        if ( $is_slider ) {
            $item_class .= ' swiper-slide';
        }

        $html  = '<div class="' . $item_class . '">';
        $html .= '<a href="' . esc_url( $board_url ) . '" target="_blank" rel="nofollow noopener" class="eael-pinterest-feed-item-inner eael-pf-board-card">';

        $html .= '<div class="eael-pf-board-cover eael-pf-board-cover-' . esc_attr( $cover_style ) . '">';
        if ( ! empty( $covers ) ) {
            foreach ( array_slice( $covers, 0, $max ) as $cover_url ) {
                $html .= '<img src="' . esc_url( $cover_url ) . '" alt="' . eael_neutralize_shortcodes( esc_attr( $board_name ) ) . '" loading="lazy">';
            }
        }
        $html .= '</div>';

        if ( $show_name || $show_pin_count ) {
            $html .= '<div class="eael-pf-board-info">';
            if ( $show_name && ! empty( $board_name ) ) {
                $html .= '<div class="eael-pf-board-name">' . eael_neutralize_shortcodes( esc_html( $board_name ) ) . '</div>';
            }
            if ( $show_pin_count ) {
                $html .= '<span class="eael-pf-board-pin-count">'
                       . esc_html( number_format_i18n( $pin_count ) ) . ' '
                       . esc_html__( 'Pins', 'essential-addons-elementor' )
                       . '</span>';
            }
            $html .= '</div>';
        }

        $html .= '</a></div>';

        return $html;
    }

    /**
     * Render all board items for the boards feed.
     *
     * @param array $boards   Array of board data.
     * @param array $settings Widget settings.
     * @return string
     */
    protected function render_board_items( $boards, $settings ) {
        $access_token = $this->get_current_pinterest_access_token( $settings );
        $html = '';

        foreach ( $boards as $board ) {
            $board_id = $board['id'] ?? '';
            if ( empty( $board_id ) ) {
                continue;
            }

            $covers = $this->fetch_board_cover_pins( $access_token, $board_id, 6 );
            $html .= $this->render_board_item( $board, $covers, $settings );
        }

        return $html;
    }
}
