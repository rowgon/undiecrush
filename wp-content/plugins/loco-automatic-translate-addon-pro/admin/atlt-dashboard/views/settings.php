<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function atlt_render_settings_page() {
    $text_domain = 'loco-translate-addon';
    
    // Define APIs configuration
    $apis = [
        'gemini' => [
            'name' => 'Gemini AI',
            'docs_url' => 'https://locoaddon.com/docs/pro-plugin/how-to-use-gemini-ai-to-translate-plugins-or-themes/generate-gemini-api-key/'
        ],
        'openai' => [
            'name' => 'OpenAI',
            'docs_url' => 'https://locoaddon.com/docs/how-to-generate-open-api-key/'
        ],
        'deepl' => [
            'name' => 'DeepL',
            'docs_url' => 'https://locoaddon.com/docs/generate-deepl-api-key-loco-ai/'
        ],
    ];
        ?>
        <div class="atlt-dashboard-settings">
            <div class="atlt-dashboard-settings-container">
                <div class="header">
                    <h1><?php esc_html_e('LocoAI Settings', $text_domain); ?></h1>
                </div>  
                <p class="description">
                    <?php 
                    printf(
                    esc_html__(
                        'Configure your settings for the LocoAI to optimize your translation experience. Start by entering your %1$slicense key%2$s. Once it\'s activated, you\'ll be able to add your Gemini or OpenAI API keys and manage your preferences for seamless integration.',
                        $text_domain
                    ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=loco-atlt-dashboard&tab=license' ) ) . '">',
                    '</a>'
                    ); 
                    ?>
            <div class="atlt-dashboard-api-settings-container">
                <div class="atlt-dashboard-api-settings">
                    <?php foreach ($apis as $key => $api): ?>
                        <label for="<?php echo esc_attr($key); ?>-api"><?php printf(__('Add %s API key', $text_domain), esc_html($api['name'])); ?></label>
                        <div class="input-group">
                            <input type="text" id="<?php echo esc_attr($key); ?>-api" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" disabled>
                        </div>
                        <?php
                        printf(
                            __('%s to See How to Generate %s API Key', $text_domain),
                            '<a href="' . esc_url($api['docs_url']) . '" target="_blank">' . esc_html__('Click Here', $text_domain) . '</a>',
                            esc_html($api['name'])
                        );
                    endforeach; ?>

                    <label for="atfp_context_aware" class="api-settings-label">
                        <?php esc_html_e( 'Context Aware', $text_domain ); ?>
                    </label>
                    <textarea
                        id="atfp_context_aware"
                        name="atfp_context_aware"
                        class="atlt-context-aware-textarea"
                        placeholder="<?php echo esc_attr__( 'Provide optional context about WordPress page or post to enhance translation accuracy (e.g. content purpose, target audience, SEO focus, tone)...', $text_domain ); ?>"
                        rows="4"
                        disabled
                    ></textarea>
                    <p class="api-settings-description" style="margin-block: 5px;">
                        <?php esc_html_e( 'This setting only works with Gemini AI and OpenAI.', $text_domain ); ?>
                    </p>

                    <div class="atlt-dashboard-save-btn-container">
                        <button disabled class="button button-primary"><?php esc_html_e('Save', $text_domain); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="atlt-dashboard-geminiAPIkey">
            <h3>Rate Limits of Free Gemini AI API Key</h3>
            <ul>
                <li><strong>15 RPM</strong>: This API Key allows a maximum of 15 requests per minute</li>
                <li><strong>1 million TPM</strong>: With this API Key, you can process up to 1 million tokens per minute</li>
                <li><strong>1,500 RPD</strong>: To ensure smooth performance, it allows up to 1,500 requests per day</li>
            </ul>
        </div> 
    </div>
    <?php
}

function atlt_render_settings_page_pro() {
    $text_domain = 'loco-translate-addon';
    
    // Move API configuration to a separate function for better organization
    $apis = atlt_get_api_configurations();
    
    // Handle form submission early
    if (atlt_check_form_submission()) {
        atlt_handle_api_key_submission();
    }
    
    atlt_render_settings_page_html($apis, $text_domain);
}

function atlt_get_api_configurations() {
    $is_wp70 = ProHelpers::is_wp_ai_client_exist();
    $credentials = get_option( 'wp_ai_client_provider_credentials', array() );
    if ( ! is_array( $credentials ) ) {
        $credentials = array();
    }

    return [
        'context_aware' => [
            'name' => 'Context Aware',
            'option_key' => 'atfp_context_aware',
            'value' => get_option( 'atfp_context_aware', '' ),
        ],
        'gemini' => [
            'name' => 'Gemini AI',
            'option_key' => $is_wp70 ? 'connectors_ai_google_api_key' : 'wp_ai_client_provider_credentials[google]',
            'docs_url' => 'https://locoaddon.com/docs/pro-plugin/how-to-use-gemini-ai-to-translate-plugins-or-themes/generate-gemini-api-key/',
            'value' => $is_wp70 ? get_option('connectors_ai_google_api_key', '') : ( $credentials['google'] ?? '' )
        ],
        'openai' => [
            'name' => 'OpenAI',
            'option_key' => $is_wp70 ? 'connectors_ai_openai_api_key' : 'wp_ai_client_provider_credentials[openai]',
            'docs_url' => 'https://locoaddon.com/docs/how-to-generate-open-api-key/',
            'value' => $is_wp70 ? get_option('connectors_ai_openai_api_key', '') : ( $credentials['openai'] ?? '' )
        ],
        'deepl' => [
            'name' => 'DeepL',
            'option_key' => $is_wp70 ? 'connectors_ai_deepl_api_key' : 'wp_ai_client_provider_credentials[deepl]',
            'docs_url' => 'https://locoaddon.com/docs/generate-deepl-api-key-loco-ai/',
            'value' => $is_wp70 ? get_option('connectors_ai_deepl_api_key', '') : ( $credentials['deepl'] ?? '' )
        ]
    ];
}

function atlt_check_form_submission() {
    if (current_user_can('manage_options')) {
    return $_SERVER['REQUEST_METHOD'] === 'POST' && 
           isset($_POST['nonce']) && 
           wp_verify_nonce($_POST['nonce'], 'api_keys');
    }
    return false;
}

function atlt_validate_google_api_key($key) {
    if (empty($key)) return false;

    if (!preg_match('/^AIza[0-9A-Za-z\-_]{35}$/', $key)) {
        atlt_show_admin_notice('error', 'Invalid Gemini AI API Key.');
        return false;
    }

    $response = wp_remote_get(
        'https://generativelanguage.googleapis.com/v1beta/models?key=' . $key,
        [
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
        ]
    );

    if (is_wp_error($response)) {
        atlt_show_admin_notice('error', 'API request failed: ' . esc_html($response->get_error_message()));
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['models']) || empty($body['models'])) {
        atlt_show_admin_notice('error', 'Invalid or unauthorized Gemini API Key.');
        return false;
    }

    $text_models = [];
    foreach ($body['models'] as $model) {
        if (
            empty($model['name']) ||
            empty($model['supportedGenerationMethods']) ||
            !is_array($model['supportedGenerationMethods']) ||
            !in_array('generateContent', $model['supportedGenerationMethods'], true)
        ) {
            continue;
        }

        $model_name = $model['name'];

        if (
            (isset($model['state']) && $model['state'] !== 'ACTIVE') ||
            preg_match('/(tts|image-generation)/i', $model_name)
        ) {
            continue;
        }

        $clean_name = str_replace('models/', '', $model_name);

        $text_models[] = $clean_name;
    }

    update_option('atlt_google_models', $text_models);

    return true;
}

function atlt_validate_openai_api_key($key) {
    if (empty($key)) return false;

    $response = wp_remote_get('https://api.openai.com/v1/models', [
        'headers' => [
            'Authorization' => 'Bearer ' . $key,
        ],
    ]);

    if (is_wp_error($response)) {
        atlt_show_admin_notice('error', 'Unable to connect to OpenAI API.');
        return false;
    }

    $response_data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($response_data['error'])) {
        $error_message = $response_data['error']['message'] ?? 'Invalid OpenAI API Key.';
        atlt_show_admin_notice('error', esc_html($error_message));
        return false;
    }

    if (empty($response_data['data'])) {
        atlt_show_admin_notice('error', 'No models found. Your API key may not have access.');
        return false;
    }

    $model_ids = array_reduce(
        $response_data['data'],
        function ( array $ids, array $model_data ) {
            $model_slug = $model_data['id'];
    
            if (
                ( str_starts_with( $model_slug, 'gpt-' ) || str_starts_with( $model_slug, 'o1-' ) )
                && ! str_contains( $model_slug, '-instruct' )
                && ! str_contains( $model_slug, '-realtime' )
                && ! str_contains( $model_slug, '-audio' )
                && ! str_contains( $model_slug, '-tts' )
                && ! str_contains( $model_slug, '-transcribe' )
                && ! str_contains( $model_slug, '-image' )
                && $model_slug !== 'o1-pro'
                && $model_slug !== 'o1-pro-2025-03-19'
            ) {
                $ids[] = $model_slug;
            }
    
            return $ids;
        },
        []
    );
    
    update_option('atlt_openai_models', $model_ids);

    return true;
}

function atlt_validate_deepl_api_key( $key ) {
    if (empty($key)) return false;

    $client = new Client();

    try {
        $response = $client->request('GET', 'https://api.deepl.com/v2/usage', [
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $key,
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $reason = $response->getReasonPhrase();

        if($statusCode === 200){
            update_option('atlt_deepl_api_key_type', 'pro');
            return true;
        }else{
            atlt_show_admin_notice('error', str_replace('<a ', '<a target="_blank" ', make_clickable($reason)));
            return false;
        }

    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            // Extract error details from response body
            $errorBody = (string) $e->getResponse()->getBody();
            
            // Decode JSON response to array
            $errorData = json_decode($errorBody, true);
    
            // Get error message if available
            $errorMessage = $errorData['message'] ?? $errorBody;
        } else {
            // Use exception message if no response body is available
            $errorMessage = $e->getMessage();
        }
    
        if(str_contains($errorMessage, 'Use https://api-free.deepl.com')){
            update_option('atlt_deepl_api_key_type', 'free');
            return true;
        }else{
            atlt_show_admin_notice('error', str_replace('<a ', '<a target="_blank" ', make_clickable($errorMessage)));
            return false;
        }
    }

    atlt_show_admin_notice('error', 'Invalid DeepL API Key.');
    return false;
}

function atlt_validate_provider_api_key( $provider_id, $api_key ) {
    if ( ! $provider_id || ! $api_key ) {
        return array( 'message' => __( 'Provider and API key are required.', 'loco-translate-addon' ) );
    }
	// Basic format validation - reject obviously invalid keys before API call.
	$key_trimmed = trim( $api_key );
	if ( strlen( $key_trimmed ) < 10 ) {
		return array( 'message' => __( 'API key appears to be invalid or too short.', 'loco-translate-addon' ) );
	}
	// Reject keys with HTML/script characters or obvious junk.
	if ( preg_match( '/[<>"\']/', $key_trimmed ) ) {
		return array( 'message' => __( 'Invalid API key format. Please check your credentials.', 'loco-translate-addon' ) );
	}
	// OpenAI keys must start with sk-
	if ( 'openai' === strtolower( $provider_id ) && ! preg_match( '/^sk-[a-zA-Z0-9_-]{20,}$/', $key_trimmed ) ) {
		return array( 'message' => __( 'OpenAI API keys must start with sk- and be in the correct format.', 'loco-translate-addon' ) );
	}

    if ( ! class_exists( 'WordPress\AiClient\AiClient' ) ) {
        return array( 'message' => __( 'AI client is not available.', 'loco-translate-addon' ) );
    }

    $registry = \WordPress\AiClient\AiClient::defaultRegistry();
    if ( ! $registry->hasProvider( $provider_id ) ) {
        return array( 'message' => __( 'Invalid AI provider.', 'loco-translate-addon' ) );
    }

    $is_gemini = ( 'google' === strtolower( $provider_id ) ) || str_contains( strtolower( $provider_id ), 'gemini' );
    $cooldown  = $is_gemini ? 60 : 5;
    $lock_key  = 'atlt_ai_test_lock_' . md5( $provider_id . '|' . $api_key );

    if ( get_transient( $lock_key ) ) {
        return array(
            'message' => $is_gemini
                ? __( 'Gemini rate limit reached. Please wait a minute and try again.', 'loco-translate-addon' )
                : __( 'Please wait a few seconds before testing again.', 'loco-translate-addon' ),
        );
    }

    // Inject the test API key into the registry (same as the REST controller does).
    $auth_class = 'WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication';
    $registry->setProviderRequestAuthentication(
        $provider_id,
        new $auth_class( $api_key )
    );

    set_transient( $lock_key, 1, $cooldown );

    try {
        $provider_classname       = $registry->getProviderClassName( $provider_id );
        $provider_availability    = $provider_classname::availability();

        if ( ! $provider_availability->isConfigured() ) {
            return array( 'message' => __( 'API key is not configured for this provider.', 'loco-translate-addon' ) );
        }

        $model_metadata_directory = $provider_classname::modelMetadataDirectory();
        $model_metadata_list = $model_metadata_directory->listModelMetadata(); // throws on invalid key

        // Persist model IDs after successful validation so the settings UI can show the model dropdown.
        // This is especially important when WP AI Client is active because we don't use the direct HTTP
        // validation methods that already populate atlt_openai_models/atlt_google_models.
        $provider_id_normalized = strtolower( (string) $provider_id );
        if ( in_array( $provider_id_normalized, array( 'openai', 'google' ), true ) ) {
            $ids = array();

            if ( is_array( $model_metadata_list ) ) {
                foreach ( $model_metadata_list as $model_meta ) {
                    $model_id = '';
                    if ( is_object( $model_meta ) && method_exists( $model_meta, 'getId' ) ) {
                        $model_id = (string) $model_meta->getId();
                    } elseif ( is_array( $model_meta ) && isset( $model_meta['id'] ) ) {
                        $model_id = (string) $model_meta['id'];
                    } elseif ( is_string( $model_meta ) ) {
                        $model_id = $model_meta;
                    }

                    $model_id = trim( $model_id );
                    if ( '' === $model_id ) {
                        continue;
                    }

                    // Prefer capability-based filtering (WP AI Client / WP 6.9 style).
                    // Only keep models that explicitly support text generation when that metadata is available.
                    $is_text_generation_capable = null;
                    if ( is_object( $model_meta ) && method_exists( $model_meta, 'getCapabilities' ) ) {
                        $caps = $model_meta->getCapabilities();
                        if ( is_array( $caps ) ) {
                            $is_text_generation_capable = false;
                            foreach ( $caps as $cap ) {
                                $cap_name = '';
                                if ( is_object( $cap ) && method_exists( $cap, 'getName' ) ) {
                                    $cap_name = (string) $cap->getName();
                                } elseif ( is_string( $cap ) ) {
                                    $cap_name = $cap;
                                } elseif ( is_object( $cap ) && method_exists( $cap, '__toString' ) ) {
                                    $cap_name = (string) $cap;
                                }

                                if ( $cap_name && preg_match( '/text\s*generation/i', $cap_name ) ) {
                                    $is_text_generation_capable = true;
                                    break;
                                }
                            }
                        }
                    }

                    if ( 'openai' === $provider_id_normalized ) {
                        if ( false === $is_text_generation_capable ) {
                            continue;
                        }

                        if (
                            ( str_starts_with( $model_id, 'gpt-' ) || str_starts_with( $model_id, 'o1-' ) )
                            && ! str_contains( $model_id, '-instruct' )
                            && ! str_contains( $model_id, '-realtime' )
                            && ! str_contains( $model_id, '-audio' )
                            && ! str_contains( $model_id, '-tts' )
                            && ! str_contains( $model_id, '-transcribe' )
                            && ! str_contains( $model_id, '-image' )
                            && $model_id !== 'o1-pro'
                            && $model_id !== 'o1-pro-2025-03-19'
                        ) {
                            $ids[] = $model_id;
                        }
                    } else { // google
                        if ( false === $is_text_generation_capable ) {
                            continue;
                        }

                        // Keep only Gemini text models; exclude obvious non-text families.
                        if (
                            ( str_starts_with( $model_id, 'gemini-' ) || str_starts_with( $model_id, 'models/gemini-' ) )
                            && ! preg_match( '/(embedding|tts|image-generation|imagen|image)/i', $model_id )
                        ) {
                            $ids[] = str_replace( 'models/', '', $model_id );
                        }
                    }
                }
            }

            $ids = array_values( array_unique( $ids ) );
            sort( $ids, SORT_STRING );

            if ( 'openai' === $provider_id_normalized ) {
                update_option( 'atlt_openai_models', $ids );
            } else {
                update_option( 'atlt_google_models', $ids );
            }
        }

    } catch ( \Exception $e ) {
        $msg = $e->getMessage();
        if ( str_contains( strtolower( $msg ), '429' ) ) {
            return array(
                'message' => $is_gemini
                    ? __( 'Gemini free tier rate limit exceeded. Please wait and try again.', 'loco-translate-addon' )
                    : __( 'Rate limit exceeded. Please try again later.', 'loco-translate-addon' ),
            );
        }
        return array( 'message' => __( 'Invalid API key. Please check your credentials.', 'loco-translate-addon' ) );
    }

    return true;
}

function atlt_show_admin_notice($type, $message) {
    if (!isset($GLOBALS['atlt_admin_notices'])) {
        $GLOBALS['atlt_admin_notices'] = array();
    }
    $GLOBALS['atlt_admin_notices'][] = '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . wp_kses($message, ['a' => ['href' => [], 'target' => []]]) . '</p></div>';
}

function atlt_handle_api_key_submission() {
    // Clear any existing notices at the start
    $GLOBALS['atlt_admin_notices'] = array();
    
    if (isset($_POST['reset_gemini_api_key'])) {
        if ( ProHelpers::is_wp_ai_client_exist() ) {
            delete_option('connectors_ai_google_api_key');
        } else {
            $credentials = get_option( 'wp_ai_client_provider_credentials', array() );
            if ( is_array( $credentials ) ) {
                unset( $credentials['google'] );
                update_option( 'wp_ai_client_provider_credentials', $credentials );
            }
        }
        delete_option('atlt_google_models');
        delete_option('atlt_selected_google_model');
        atlt_show_admin_notice('success', 'Gemini AI API Key has been removed.');
        return true;
    } 
    
    if (isset($_POST['reset_openai_api_key'])) {
        if ( ProHelpers::is_wp_ai_client_exist() ) {
            delete_option('connectors_ai_openai_api_key');
        } else {
            $credentials = get_option( 'wp_ai_client_provider_credentials', array() );
            if ( is_array( $credentials ) ) {
                unset( $credentials['openai'] );
                update_option( 'wp_ai_client_provider_credentials', $credentials );
            }
        }
        delete_option('atlt_openai_models');
        delete_option('atlt_selected_openai_model');
        atlt_show_admin_notice('success', 'OpenAI API Key has been removed.');
        return true;
    }

    if (isset($_POST['reset_deepl_api_key'])) {
        if ( ProHelpers::is_wp_ai_client_exist() ) {
            delete_option('connectors_ai_deepl_api_key');
        } else {
            $credentials = get_option( 'wp_ai_client_provider_credentials', array() );
            if ( is_array( $credentials ) ) {
                unset( $credentials['deepl'] );
                update_option( 'wp_ai_client_provider_credentials', $credentials );
            }
        }
        atlt_show_admin_notice('success', 'DeepL API Key has been removed.');
        return true;
    }
    
    if (isset($_POST['submit_api_keys'])) {
        return atlt_handle_api_key_save();
    }
    
    return false;
}

function atlt_handle_api_key_save() {

    $success = false;
    $any_validation_attempted = false;

    // Handle Context Aware textarea (optional)
    if ( isset( $_POST['atlt_context_aware'] ) ) {
        $context_aware = sanitize_textarea_field( wp_unslash( $_POST['atlt_context_aware'] ) );
        update_option( 'atlt_context_aware', $context_aware );
        $success = true;
    }
    
    $current_openai_model = get_option('atlt_selected_openai_model', '');
    $current_google_model = get_option('atlt_selected_google_model', '');
    $current_deepl_model = get_option('atlt_selected_deepl_model', '');

    // Save selected OpenAI model if set and validate
    if (isset($_POST['atlt_selected_openai_model']) && $_POST['atlt_selected_openai_model'] !== $current_openai_model) {

        $selected_model = sanitize_text_field($_POST['atlt_selected_openai_model']);
        $openai_key = ProHelpers::is_wp_ai_client_exist()
            ? get_option( 'connectors_ai_openai_api_key', '' )
            : ( ( get_option( 'wp_ai_client_provider_credentials', array() ) )['openai'] ?? '' );
        $is_valid = false;
        $error_message = '';
    
        if ($selected_model === '') {
            update_option('atlt_selected_openai_model', '');
            atlt_show_admin_notice('success', 'OpenAI model selection has been cleared.');
        } else {
            if ($openai_key && $selected_model) {
                $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $openai_key,
                    ],
                    'body' => wp_json_encode([
                        'model' => $selected_model,
                        'messages' => [['role' => 'user', 'content' => 'Test']],
                        'max_completion_tokens' => 20
                    ]),
                    'timeout' => 20,
                ]);
    
                if (is_wp_error($response)) {
                    $error_message = esc_html($response->get_error_message());
                } else {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (empty($body['error'])) {
                        $is_valid = true;
                    } else {
                        $error_message = esc_html($body['error']['message'] ?? 'Unknown error from OpenAI.');
                    }
                }
            }
    
            if ($is_valid) {
                update_option('atlt_selected_openai_model', $selected_model);
                atlt_show_admin_notice('success', 'The OpenAI model has been successfully validated and saved.');
            } else {
                atlt_show_admin_notice('error', 'OpenAI API Error: ' . $error_message);
            }
        }
    }    


    if (isset($_POST['atlt_selected_google_model']) && $_POST['atlt_selected_google_model'] !== $current_google_model) {

        $selected_model = sanitize_text_field($_POST['atlt_selected_google_model']);
        $google_key = ProHelpers::is_wp_ai_client_exist()
            ? get_option( 'connectors_ai_google_api_key', '' )
            : ( ( get_option( 'wp_ai_client_provider_credentials', array() ) )['google'] ?? '' );
        $is_valid = false;
        $error_message = '';
    
        if ($selected_model === '') {
            delete_option('atlt_selected_google_model');
            atlt_show_admin_notice('success', 'The Google Gemini model selection has been cleared.');
        } else {
            if ($google_key && $selected_model) {
                $response = wp_remote_post(
                    'https://generativelanguage.googleapis.com/v1beta/models/' . $selected_model . ':generateContent?key=' . $google_key,
                    [
                        'headers' => ['Content-Type' => 'application/json'],
                        'body'    => json_encode([
                            'contents' => [[ 'parts' => [['text' => 'Test']] ]]
                        ]),
                        'timeout' => 60,
                    ]
                );
    
                if (!is_wp_error($response)) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (empty($body['error'])) {
                        $is_valid = true;
                    } else if (!empty($body['error']['message'])) {
                        $error_message = esc_html($body['error']['message']);
                    }
                } else {
                    $error_message = esc_html($response->get_error_message());
                }
            }
    
            if ($is_valid) {
                update_option('atlt_selected_google_model', $selected_model);
                atlt_show_admin_notice('success', 'The Gemini model has been successfully validated and saved.');
            } else {
                $notice = $error_message ? $error_message : 'The selected Gemini model is not valid or not accessible with your API key.';
                atlt_show_admin_notice('error', $notice);
            }
        }
    }

    if (isset($_POST['atlt_selected_deepl_model']) && $_POST['atlt_selected_deepl_model'] !== $current_deepl_model) {
        $selected_model = sanitize_text_field($_POST['atlt_selected_deepl_model']);
        $deepl_key = ProHelpers::is_wp_ai_client_exist()
            ? get_option( 'connectors_ai_deepl_api_key', '' )
            : ( ( get_option( 'wp_ai_client_provider_credentials', array() ) )['deepl'] ?? '' );
        $is_valid = false;
        $error_message = '';
        
    }

    $feedback_opt_in = null; 
    
    // Handle feedback checkbox
    if (get_option('cpfm_opt_in_choice_cool_translations')) {

        $feedback_opt_in = isset($_POST['atlt-dashboard-feedback-checkbox']) ? 'yes' : 'no';
        update_option('atlt_feedback_opt_in', $feedback_opt_in);
      
    }
    

    // If user opted out, remove the cron job
    if ($feedback_opt_in === 'no' && wp_next_scheduled('atlt_extra_data_update') ){
        
        wp_clear_scheduled_hook('atlt_extra_data_update');
     
    }

    if ($feedback_opt_in === 'yes' && !wp_next_scheduled('atlt_extra_data_update')) {

            wp_schedule_event(time(), 'every_30_days', 'atlt_extra_data_update');

            if (class_exists('ATLT_cronjob')) {

                ATLT_cronjob::atlt_send_data();
            } 
    }
    
    $is_wp70 = ProHelpers::is_wp_ai_client_exist();
    $google_post_key = $is_wp70 ? 'connectors_ai_google_api_key' : 'wp_ai_client_provider_credentials';
    if ( $is_wp70 && isset( $_POST[ $google_post_key ] ) ) {
        $new_google_key = sanitize_text_field( wp_unslash( $_POST[ $google_post_key ] ) );
        if (!empty($new_google_key)) {
            $any_validation_attempted = true;
            $validate_api_key = atlt_validate_provider_api_key( 'google', $new_google_key );
            if ( $validate_api_key === true ) {
                update_option('connectors_ai_google_api_key', $new_google_key);
                $success = true;
            }
        }
    }
    if ( ! $is_wp70 && isset( $_POST['wp_ai_client_provider_credentials'] ) && is_array( $_POST['wp_ai_client_provider_credentials'] ) ) {
        $posted = wp_unslash( $_POST['wp_ai_client_provider_credentials'] );
        $new_google_key = isset( $posted['google'] ) ? sanitize_text_field( $posted['google'] ) : '';
        if ( $new_google_key !== '' ) {
            $any_validation_attempted = true;
            // On WP < 7.0, validate directly (providers may not be registered in the SDK registry yet).
            $validate_api_key = atlt_validate_google_api_key( $new_google_key );
            if ( $validate_api_key === true ) {
                $credentials = get_option( 'wp_ai_client_provider_credentials', array() );
                if ( ! is_array( $credentials ) ) {
                    $credentials = array();
                }
                $credentials['google'] = $new_google_key;
                update_option( 'wp_ai_client_provider_credentials', $credentials );
                $success = true;
            }
        }
    }
    
    $openai_post_key = $is_wp70 ? 'connectors_ai_openai_api_key' : 'wp_ai_client_provider_credentials';
    if ( $is_wp70 && isset( $_POST[ $openai_post_key ] ) ) {
        $new_openai_key = sanitize_text_field( wp_unslash( $_POST[ $openai_post_key ] ) );
        if (!empty($new_openai_key)) {
            $any_validation_attempted = true;
            $validate_api_key = atlt_validate_provider_api_key( 'openai', $new_openai_key );
            if ( $validate_api_key === true ) {
                update_option('connectors_ai_openai_api_key', $new_openai_key);
                $success = true;
            }
        }
    }
    if ( ! $is_wp70 && isset( $_POST['wp_ai_client_provider_credentials'] ) && is_array( $_POST['wp_ai_client_provider_credentials'] ) ) {
        $posted = wp_unslash( $_POST['wp_ai_client_provider_credentials'] );
        $new_openai_key = isset( $posted['openai'] ) ? sanitize_text_field( $posted['openai'] ) : '';
        if ( $new_openai_key !== '' ) {
            $any_validation_attempted = true;
            // On WP < 7.0, validate directly (providers may not be registered in the SDK registry yet).
            $validate_api_key = atlt_validate_openai_api_key( $new_openai_key );
            if ( $validate_api_key === true ) {
                $credentials = get_option( 'wp_ai_client_provider_credentials', array() );
                if ( ! is_array( $credentials ) ) {
                    $credentials = array();
                }
                $credentials['openai'] = $new_openai_key;
                update_option( 'wp_ai_client_provider_credentials', $credentials );
                $success = true;
            }
        }
    }

    $deepl_post_key = $is_wp70 ? 'connectors_ai_deepl_api_key' : 'wp_ai_client_provider_credentials';
    if ( $is_wp70 && isset( $_POST[ $deepl_post_key ] ) ) {
        $new_deepl_key = sanitize_text_field( wp_unslash( $_POST[ $deepl_post_key ] ) );
        if (!empty($new_deepl_key)) {
            $any_validation_attempted = true;
            if ( atlt_validate_deepl_api_key($new_deepl_key) ) {
                update_option('connectors_ai_deepl_api_key', $new_deepl_key);
                $success = true;
            }
        }
    }
    if ( ! $is_wp70 && isset( $_POST['wp_ai_client_provider_credentials'] ) && is_array( $_POST['wp_ai_client_provider_credentials'] ) ) {
        $posted = wp_unslash( $_POST['wp_ai_client_provider_credentials'] );
        $new_deepl_key = isset( $posted['deepl'] ) ? sanitize_text_field( $posted['deepl'] ) : '';
        if ( $new_deepl_key !== '' ) {
            $any_validation_attempted = true;
            if ( atlt_validate_deepl_api_key($new_deepl_key) ) {
                $credentials = get_option( 'wp_ai_client_provider_credentials', array() );
                if ( ! is_array( $credentials ) ) {
                    $credentials = array();
                }
                $credentials['deepl'] = $new_deepl_key;
                update_option( 'wp_ai_client_provider_credentials', $credentials );
                $success = true;
            }
        }
    }
    
    if ($success) {
        atlt_show_admin_notice('success', 'API keys saved successfully.');
        return true;
    } elseif ($any_validation_attempted && !isset($GLOBALS['atlt_admin_notices'])) {
        // Only show generic error if we attempted validation and no specific error was set
        atlt_show_admin_notice('error', 'Please enter a valid API key.');
    }
    
    return false;
}

function atlt_render_settings_page_html($apis, $text_domain) {
    // Process form submission before rendering
    $form_processed = false;
    if (atlt_check_form_submission()) {
        $form_processed = atlt_handle_api_key_submission();
    }
    
    // Refresh API values after form processing
    if ($form_processed) {
        $apis = atlt_get_api_configurations();
    }
    
    // Get available models for each API
    $openai_models = get_option('atlt_openai_models', []);
    $google_models = get_option('atlt_google_models', []);
    $current_openai_model = get_option('atlt_selected_openai_model', '');
    $current_google_model = get_option('atlt_selected_google_model', '');
    $context_value = get_option( 'atlt_context_aware', '' );
    
    ?>
    <div class="atlt-dashboard-settings">
        <div class="atlt-dashboard-settings-container">
            <?php
            // Show notices at the top of the container
            if (isset($GLOBALS['atlt_admin_notices'])) {
                foreach ($GLOBALS['atlt_admin_notices'] as $notice) {
                    echo wp_kses_post($notice);
                }
            }
            ?>
            <div class="header">
                <h1><?php esc_html_e('LocoAI Settings', $text_domain); ?></h1>
            </div>
            
            <p class="description">
                <?php esc_html_e('Configure your settings for the LocoAI to optimize your translation experience. Enter your API keys and manage your preferences for seamless integration.', $text_domain); ?>
            </p>

            <div class="atlt-dashboard-api-settings-container">
                <div class="atlt-dashboard-api-settings">
                    <form method="post">
                        <div class="atlt-dashboard-api-settings-form">
                            <?php wp_nonce_field('api_keys', 'nonce'); ?>
                            
                            <?php foreach ($apis as $key => $api):
                                if ( 'context_aware' === $key ) {
                                    continue;
                                }
                                $has_key = !empty($api['value']);
                                $masked_value = $has_key ? 
                                    esc_attr(substr($api['value'], 0, 8) . str_repeat('*', 24) . substr($api['value'], -8) . ' ✅') : 
                                    ''; 
                            ?>
                                <label for="<?php echo esc_attr($key); ?>-api" class="api-settings-label">
                                    <?php printf(__('Add %s API key', $text_domain), esc_html($api['name'])); ?>
                                </label>
                                <div class="input-group">
                                    <input type="text" 
                                        id="<?php echo esc_attr($key); ?>-api" 
                                        name="<?php echo esc_attr($api['option_key']); ?>" 
                                        value="<?php echo esc_attr($masked_value); ?>" 
                                        placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                        <?php echo $has_key ? 'disabled' : ''; ?>>
                                    
                                    <?php if ($has_key): ?>
                                        <button type="submit" name="reset_<?php echo esc_attr($key); ?>_api_key" class="button button-primary">
                                            <?php esc_html_e('Reset', $text_domain); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!$has_key): ?>
                                    <?php
                                    printf(
                                        __('%s to See How to Generate %s API Key', $text_domain),
                                        '<a href="' . esc_url($api['docs_url']) . '" target="_blank">' . esc_html__('Click Here', $text_domain) . '</a>',
                                        esc_html($api['name'])
                                    );
                                    ?>
                                <?php endif; ?>
                                    <?php 
                                    if ($key === 'openai' && $has_key && !empty($openai_models)) : ?>
                                        <div class="atlt-dashboard-api-settings-openai-model">
                                            <label for="atlt_selected_openai_model" class="api-settings-label">
                                                <?php esc_html_e('Select OpenAI Model', $text_domain); ?>
                                            </label>
                                            <select name="atlt_selected_openai_model" class="atlt-openai-model-select">
                                                <option value=""><?php esc_html_e('Select model...', $text_domain); ?></option>
                                                <?php foreach ($openai_models as $model) : ?>
                                                    <option value="<?php echo esc_attr($model); ?>" <?php selected($current_openai_model, $model); ?>>
                                                        <?php echo esc_html($model); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($key === 'gemini' && $has_key && !empty($google_models)) : ?>
                                        <div class="atlt-dashboard-api-settings-google-model">
                                            <label for="atlt_selected_google_model" class="api-settings-label">
                                                <?php esc_html_e('Select Gemini Model', $text_domain); ?>
                                            </label>
                                            <select name="atlt_selected_google_model" class="atlt-google-model-select">
                                                <option value=""><?php esc_html_e('Select model...', $text_domain); ?></option>
                                                <?php foreach ($google_models as $model) : ?>
                                                    <option value="<?php echo esc_attr($model); ?>" <?php selected($current_google_model, $model); ?>>
                                                        <?php echo esc_html($model); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                            <?php endforeach; ?>

                            <label for="atlt_context_aware" class="api-settings-label">
                                <?php esc_html_e( 'Context Aware', $text_domain ); ?>
                            </label>
                            <textarea
                                id="atlt_context_aware"
                                name="atlt_context_aware"
                                class="atlt-context-aware-textarea"
                                placeholder="<?php echo esc_attr__( 'Provide optional context about WordPress page or post to enhance translation accuracy (e.g. content purpose, target audience, SEO focus, tone)...', $text_domain ); ?>"
                                rows="4"
                            ><?php echo esc_textarea( $context_value ); ?></textarea>
                            <p class="api-settings-description" style="margin-block: 5px;">
                                <?php esc_html_e( 'This setting only works with Gemini AI and OpenAI.', $text_domain ); ?>
                            </p>
                        </div>

                            <?php if (get_option('cpfm_opt_in_choice_cool_translations')) : ?>
                              
                            <div class="atlt-dashboard-feedback-container">
                                <div class="feedback-row">
                                    <input type="checkbox" 
                                        id="atlt-dashboard-feedback-checkbox" 
                                        name="atlt-dashboard-feedback-checkbox"
                                        <?php checked(get_option('atlt_feedback_opt_in'), 'yes'); ?>>
                                    <p><?php esc_html_e('Help us make this plugin more compatible with your site by sharing non-sensitive site data.', $text_domain); ?></p>
                                    <a href="#" class="atlt-see-terms">[See terms]</a>
                                </div>
                                <div id="termsBox" style="display: none;padding-left: 20px; margin-top: 10px; font-size: 12px; color: #999;">
                                <p><?php esc_html_e("Opt in to receive email updates about security improvements, new features, helpful tutorials, and occasional special offers. We'll collect: ", 'ccpw'); ?><a href="https://my.coolplugins.net/terms/usage-tracking/" target="_blank"> Click here</a></p>
                                        <ul style="list-style-type:auto;">
                                            <li><?php esc_html_e('Your website home URL and WordPress admin email.', 'ccpw'); ?></li>
                                            <li><?php esc_html_e('To check plugin compatibility, we will collect the following: list of active plugins and themes, server type, MySQL version, WordPress version, memory limit, site language and database prefix.', 'ccpw'); ?></li>
                                        </ul>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="atlt-dashboard-save-btn-container">
                                <button type="submit" name="submit_api_keys" class="button button-primary">
                                    <?php esc_html_e('Save', $text_domain); ?>
                                </button>
                            </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="atlt-dashboard-geminiAPIkey">
            <h3>Rate Limits of Free Gemini AI API Key</h3>
            <ul>
                <li><strong>15 RPM</strong>: This API Key allows a maximum of 15 requests per minute</li>
                <li><strong>1 million TPM</strong>: With this API Key, you can process up to 1 million tokens per minute</li>
                <li><strong>1,500 RPD</strong>: To ensure smooth performance, it allows up to 1,500 requests per day</li>
            </ul>
        </div> 
    </div>
    <?php
}



