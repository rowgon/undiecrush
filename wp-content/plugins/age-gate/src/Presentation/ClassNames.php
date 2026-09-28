<?php

namespace AgeGate\Presentation;

use AgeGate\Common\Settings;
use Asylum\Utility\StringTemplate;

trait ClassNames
{
    protected static $formatted = false;

    public static $attributes = [
        'age-gate-html' => [
            'class' => 'age-gate{element}restricted age-gate{element}restricted{modifier}standard'
        ],
        'age-gate-body' => [
            'class' => 'age-restriction'
        ],
        'age-gate-headline' => [
            'class' => 'age-gate{element}headline'
        ],
        'age-gate-wrapper' => [
            'class' => 'age-gate{element}wrapper'
        ],
        'age-gate-background-colour' => [
            'class' => 'age-gate{element}background-color'
        ],
        'age-gate-background' => [
            'class' => 'age-gate{element}background'
        ],
        'age-gate-loader' => [
            'class' => 'age-gate{element}loader'
        ],
        'age-gate-loading-icon' => [
            'class' => 'age-gate{element}loading-icon'
        ],
        'age-gate' => [
            'class' => 'age-gate'
        ],
        'age-gate-shortcode' => [
            'class' => 'age-gate age-gate{modifier}shortcode'
        ],
        'age-gate-shortcode-inner' => [
            'class' => 'age-gate{element}shortcode{element}inner'
        ],
        'age-gate-form' => [
            'class' => 'age-gate{element}form'
        ],
        'age-gate-errors' => [
            'class' => 'age-gate{element}errors'
        ],
        'age-gate-error' => [
            'class' => 'age-gate{element}error'
        ],
        'age-gate-error-message' => [
            'class' => 'age-gate{element}error-message'
        ],
        'age-gate-remember-wrapper' => [
            'class' => 'age-gate{element}remember-wrapper'
        ],
        'age-gate-remember' => [
            'class' => 'age-gate{element}remember'
        ],
        'age-gate-remember-checkbox' => [
            'type' => 'checkbox',
            'class' => 'age-gate{element}remember-field',
            'name' => 'age_gate[remember]',
            'value' => 1
        ],
        'age-gate-remember-text' => [
            'type' => 'checkbox',
            'class' => 'age-gate{element}remember-text',
        ],
        'age-gate-submit-section' => [
            'class' => 'age-gate{element}submit'
        ],
        'age-gate-submit' => [
            'class' => 'age-gate{element}button'
        ],
        'age-gate-additional-information' => [
            'class' => 'age-gate{element}additional-information'
        ],
        'age-gate-form-fields' => [
            'class' => 'age-gate{element}fields'
        ],
        'age-gate-form-elements' => [
            'class' => 'age-gate{element}form-elements'
        ],
        'age-gate-form-section' => [
            'class' => 'age-gate{element}form-section'
        ],
        'age-gate-d-label' => [
            'class' => 'age-gate{element}label age-gate{element}label{modifier}day',
            'for' => 'age-gate-d'
        ],
        'age-gate-m-label' => [
            'class' => 'age-gate{element}label age-gate{element}label{modifier}month',
            'for' => 'age-gate-m'
        ],
        'age-gate-y-label' => [
            'class' => 'age-gate{element}label age-gate{element}label{modifier}year',
            'for' => 'age-gate-y'
        ],
        'age-gate-d-input' => [
            'class' => 'age-gate{element}input age-gate{element}input{modifier}day',
            'type' => "text",
            'name' => "age_gate[d]",
            'id' => "age-gate-d",
            'maxlength' => "2",
            'pattern' => "[0-9]*" ,
            'inputmode' => "numeric" ,
            'autocomplete' => "off",
        ],
        'age-gate-m-input' => [
            'class' => 'age-gate{element}input age-gate{element}input{modifier}month',
            'type' => "text",
            'name' => "age_gate[m]",
            'id' => "age-gate-m",
            'maxlength' => "2",
            'pattern' => "[0-9]*",
            'inputmode' => "numeric",
            'autocomplete' => "off",
        ],
        'age-gate-y-input' => [
            'class' => 'age-gate{element}input age-gate{element}input{modifier}year',
            'type' => "text",
            'name' => "age_gate[y]",
            'id' => "age-gate-y",
            'minlength' => "4",
            'maxlength' => "4",
            'pattern' => "[0-9]*",
            'inputmode' => "numeric",
            'autocomplete' => "off",
        ],
        'age-gate-d-select' => [
            'class' => 'age-gate{element}select',
            'name' => 'age_gate[d]',
            'id' => 'age-gate-d',
        ],
        'age-gate-m-select' => [
            'class' => 'age-gate{element}select',
            'name' => 'age_gate[m]',
            'id' => 'age-gate-m',
        ],
        'age-gate-y-select' => [
            'class' => 'age-gate{element}select',
            'name' => 'age_gate[y]',
            'id' => 'age-gate-y',
        ],
        'age-gate-challenge' => [
            'class' => 'age-gate{element}challenge'
        ],
        'age-gate-buttons' => [
            'class' => 'age-gate{element}buttons',
        ],
        'age-gate-submit-yes' => [
            'class' => 'age-gate{element}submit age-gate{element}submit{modifier}yes',
            'data-submit' => 'yes',
            'value' => 1,
            "name" => 'age_gate[confirm]'
        ],
        'age-gate-submit-no' => [
            'class' => 'age-gate{element}submit age-gate{element}submit{modifier}no',
            'data-submit' => 'no',
            'value' => 0,
            "name" => 'age_gate[confirm]'
        ],
        'age-gate-heading' => [
            'class' => 'age-gate{element}heading'
        ],
        'age-gate-heading-title' => [
            'class' => 'age-gate{element}heading-title'
        ],
        'age-gate-logo' => [
            'class' => 'age-gate{element}heading-title age-gate{element}heading-title{modifier}logo'
        ],
        'age-gate-logo-image' => [
            'class' => 'age-gate{element}logo-image'
        ],
        'age-gate-subheadline' => [
            'class' => 'age-gate{element}subheadline'
        ],
        'age-gate-video' => [
            'class' => 'age-gate{element}video'
        ],
        'age-gate-extra' => [
            'class' => 'age-gate{element}extra'
        ],
        'age-gate-template' => [
            'class' => '',
        ]
    ];

    public static $disallowedAttributes = [
        'type',
        'value',
        'pattern',
        'name',
        'inputmode',
        'autocomplete',
        'id',
        'for',
        'minlength',
        'maxlength',
        'required',
        'data-error-field',
        'method',
        'action',
        'onclick',
        'onload',
        'onerror',
        'disabled',
        'accept',
        'accept-charset',
        'action',
        'align',
        'allow',
        'as',
        'async',
        'autocapitalize',
        'autocomplete',
        'autoplay',
        'bgcolor',
        'border',
        'capture',
        'charset',
        'checked',
        'cite',
        'color',
        'cols',
        'colspan',
        'content',
        'contenteditable',
        'controls',
        'coords',
        'crossorigin',
        'csp',
        'datetime',
        'decoding',
        'default',
        'defer',
        'dir',
        'dirname',
        'disabled',
        'download',
        'draggable',
        'enctype',
        'enterkeyhint',
        'for',
        'form',
        'formaction',
        'headers',
        'height',
        'hidden',
        'high',
        'href',
        'hreflang',
        'http-equiv',
        'id',
        'integrity',
        'intrinsicsize',
        'inputmode',
        'ismap',
        'itemprop',
        'kind',
        'label',
        'lang',
        'language',
        'loading',
        'list',
        'loop',
        'low',
        'media',
        'method',
        'multiple',
        'muted',
        'name',
        'novalidate',
        'open',
        'optimum',
        'pattern',
        'ping',
        'playsinline',
        'poster',
        'preload',
        'readonly',
        'referrerpolicy',
        'required',
        'reversed',
        'rows',
        'rowspan',
        'sandbox',
        'scope',
        'scoped',
        'selected',
        'shape',
        'size',
        'sizes',
        'slot',
        'span',
        'spellcheck',
        'src',
        'srcdoc',
        'srclang',
        'srcset',
        'start',
        'step',
        'style',
        'summary',
        'target',
        'translate',
        'type',
        'usemap',
        'value',
        'width',
        'wrap',
        'formenctype',
        'formmethod',
        'formnovalidate',
        'formtarget',
    ];

    protected static $validAttributes = [
        'accesskey',
        'alt',
        'background',
        'class',
        'data',
        'data-*',
        'max',
        'maxlength',
        'minlength',
        'min',
        'placeholder',
        'rel',
        'role',
        'tabindex',
        'title',
        'aria-autocomplete',
        'aria-checked',
        'aria-disabled',
        'aria-errormessage',
        'aria-expanded',
        'aria-haspopup',
        'aria-hidden',
        'aria-invalid',
        'aria-label',
        'aria-level',
        'aria-modal',
        'aria-multiline',
        'aria-multiselectable',
        'aria-orientation',
        'aria-placeholder',
        'aria-pressed',
        'aria-readonly',
        'aria-required',
        'aria-selected',
        'aria-sort',
        'aria-valuemax',
        'aria-valuemin',
        'aria-valuenow',
        'aria-valuetext',
        'aria-busy',
        'aria-live',
        'aria-relevant',
        'aria-atomic',
        'aria-activedescendant',
        'aria-colcount',
        'aria-colindex',
        'aria-colspan',
        'aria-controls',
        'aria-describedby',
        'aria-description',
        'aria-details',
        'aria-errormessage',
        'aria-flowto',
        'aria-labelledby',
        'aria-owns',
        'aria-posinset',
        'aria-rowcount',
        'aria-rowindex',
        'aria-rowspan',
        'aria-setsize',
        'aria-atomic',
        'aria-busy',
        'aria-controls',
        'aria-current',
        'aria-describedby',
        'aria-description',
        'aria-details',
        'aria-disabled',
        'aria-dropeffect',
        'aria-errormessage',
        'aria-flowto',
        'aria-grabbed',
        'aria-haspopup',
        'aria-hidden',
        'aria-invalid',
        'aria-keyshortcuts',
        'aria-label',
        'aria-labelledby',
        'aria-live',
        'aria-owns',
        'aria-relevant',
        'aria-roledescription',
    ];

    public static $months = [
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December'
    ];

    public static function addAttribute(string $element, $attribute, string $value): void
    {
        if (in_array($attribute, self::$disallowedAttributes)) {
            _doing_it_wrong('age_gate_add_attribute', esc_html(wp_sprintf('`%s` may not be assigned', $attribute)), esc_html(AGE_GATE_VERSION));
            return;
            // wp_die('Disallowed attribute: <b>"' . $attribute . '"</b> on <b>"' . $element . '"</b>');
        }

        if (!in_array($attribute, self::$validAttributes) && strpos($attribute, 'data-') !== 0) {
            _doing_it_wrong('age_gate_add_attribute', esc_html(wp_sprintf('Invalid attribute', $attribute)), esc_html(AGE_GATE_VERSION));
            return;
        }

        if (is_array($attribute)) {
            foreach ($attribute as $k => $value) {
                self::addAttribute($element, $k, $value);
            }
        } else {

            if (!isset(self::$attributes[$element])) {
                return;
            }

            $attribute = sanitize_text_field( $attribute);

            if (isset(self::$attributes[$element][$attribute])) {
                if (strpos(self::$attributes[$element][$attribute], $value) === false) {
                    self::$attributes[$element][$attribute] = self::$attributes[$element][$attribute] . ' ' . $value;
                }
            } else {
                self::$attributes[$element][$attribute] =  $value;
            };
        }

    }

    public static function getAttribute($element)
    {
        if (isset(self::$attributes[$element])) {
            return html_build_attributes(self::$attributes[$element]);
        } else {
            return html_build_attributes(['class' => $element]);
        }
        // return self::$attributes[$element];
    }

    public static function attr($element, $echo = true)
    {
        if (!self::$formatted) {
            self::formatClasses();
        }

        if ($echo) {
            echo self::getAttribute($element); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return;
        }

        return self::getAttribute($element);
    }

    public static function formatClasses()
    {
        $settings = Settings::getInstance();

        $replacements = [
            'element' => ($settings->cssType === 'v2' ? '-' : '__'),
            'modifier' => ($settings->cssType === 'v2' ? '-' : '--'),
        ];

        $engine = new StringTemplate();

        foreach (self::$attributes as $key => $attribute) {
            if ($attribute['class']) {
                self::$attributes[$key]['class'] = $engine->render($attribute['class'], $replacements);
            }
        }

        self::$formatted = true;
    }

    public static function formatMessage($str, $replacements = [])
    {
        $engine = new StringTemplate();
        return $engine->render($str, $replacements);
    }

    public static function getSingleAttribute($element, $attribute)
    {
        return self::$attributes[$element][$attribute] ?? false;
    }
}
