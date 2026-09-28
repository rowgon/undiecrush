<?php

namespace AgeGate\Admin\Taxonomy;

use AgeGate\Common\Content;
use AgeGate\Common\Settings;
use Asylum\Validation\Validator;
use Jawira\CaseConverter\Convert;
use AgeGate\Common\Immutable\Constants;

class Term
{
    private $settings;
    private $view;

    public function __construct($view)
    {
        $this->view = $view;
        $this->settings = Settings::getInstance();

        add_action('admin_init', [$this, 'registerFields']);
    }

    public function registerFields()
    {
        $args = [
            'public' => true,
            'show_ui' => true,
        ];

        foreach (get_taxonomies($args) as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', [$this, 'index']);
            add_action($taxonomy . '_edit_form_fields', [$this, 'index']);

            add_action('create_' . $taxonomy, [$this, 'store'], 10, 2);
            add_action('edited_' . $taxonomy, [$this, 'store'], 10, 2);
        }
    }

    public function index()
    {
        if (current_user_can(Constants::SET_CONTENT) || current_user_can(Constants::SET_CUSTOM_AGE)) {
            global $typenow;


            $settings = Settings::getInstance();
            $disable = $this->settings->disable[$typenow] ?? false;

            if ($disable) {
                return;
            }

            global $tag;

            $id = ($tag) ? (int) $tag->term_id : null;

            $viewData = [
                'content' => new Content($id, 'term'),
                'action' => strpos(current_action(), 'edit') !== false ? 'edit' : 'add',
                'settings' => $settings,
                'setRestriction' => current_user_can(Constants::SET_CONTENT),
                'setAge' => current_user_can(Constants::SET_CUSTOM_AGE),
                'contentOption' => $this->settings->type === 'selected' ? Constants::META_RESTRICT : Constants::META_BYPASS,
            ];

            echo $this->view->addData($viewData)->render('term/meta-options'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    public function store($id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // check nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_agn'] ?? '')), 'age_gate_post_edit')) {
            return;
        }

        $validator = new Validator;
        $content = new Content($id, 'term');
        $postData = $validator->sanitize(wp_unslash($_POST['ag_settings'] ?? [])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        // mutli ages?
        if ($this->settings->multiAge && current_user_can(Constants::SET_CUSTOM_AGE)) {
            $default = $this->settings->{$content->getLanguage()}['defaultAge'] ?? $this->settings->defaultAge;

            if ( $postData['age'] ?? false) {
                $age = (int) wp_unslash( $postData['age']);

                if ($age === $default) {
                    // remove the meta as we don't need it
                    delete_term_meta($id, Constants::META_AGE);
                } else {
                    // add new meta key
                    update_term_meta($id, Constants::META_AGE, $age);
                }
            }
        }

        // bypass ?
        if ($this->settings->type === 'all' && current_user_can(Constants::SET_CONTENT)) {
            if ( $postData['bypass'] ?? false) {
                // add new meta key
                update_term_meta($id, Constants::META_BYPASS, 1);
            } else {
                // remove the meta as we don't need it
                delete_term_meta($id, Constants::META_BYPASS);
            }
        }

        // restrict
        if ($this->settings->type === 'selected' && current_user_can(Constants::SET_CONTENT)) {
            if ( $postData['restrict'] ?? false) {
                // add new meta key
                update_term_meta($id, Constants::META_RESTRICT, 1);
            } else {
                // remove the meta as we don't need it
                delete_term_meta($id, Constants::META_RESTRICT);
            }
        }
    }
}
