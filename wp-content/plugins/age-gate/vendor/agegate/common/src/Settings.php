<?php

namespace AgeGate\Common;

use Illuminate\Support\Arr;
use Asylum\Utility\Language;
use Jawira\CaseConverter\Convert;
use AgeGate\Common\Immutable\Constants;

class Settings
{
    private $data = [];
    public $currentLanguage;

    private static $instance = null;

    private function __construct()
    {

        $this->currentLanguage = !empty($_REQUEST['age_gate']['lang']) ? sanitize_file_name($_REQUEST['age_gate']['lang']) : Language::getInstance()->getLanguage();
        // $this->data['language'] = $this->currentLanguage;

        // dump(Constants::AGE_GATE_OPTIONS);

        $options = [];
        $data = [
            'language' => $this->currentLanguage,
        ];

        foreach (Constants::AGE_GATE_OPTIONS as $slug => $option) {
            if ($slug === 'access') {
                continue;
            }

            $group = get_option($option, []);
            $options = array_merge($options, Arr::dot($group));
        }

        foreach (Arr::undot(array_filter($options, function ($value) {
            return ($value || is_numeric($value));
        })) as $key => $value) {

            $data[(new Convert($key))->toCamel()] = stripslashes_deep($value);
        }

        // languages
        foreach (Language::getInstance()->getLanguages() as $code => $language) {
            $data[(new Convert($code))->toCamel()] = collect($data[(new Convert($code))->toCamel()] ?? [])
                ->mapWithKeys(fn ($value, $key) => [
                    (new Convert($key))->toCamel() => stripslashes_deep($value)
                ])
                ->toArray();
        }

        // flip terms
        $data['terms'] = collect($data['terms'] ?? [])->map(function ($item, $k) {
            return is_array($item) ? array_keys($item) : [];
        })->toArray();

        $data['rememberTime'] = $data['rememberLength']['remember_time'] ?? 365;
        $data['rememberLength'] = $data['rememberLength']['remember_length'] ?? 'days';

        $data['logoId'] = (int) apply_filters('age_gate/logo/id', $data['logo'] ?? null);

        $imageMeta = wp_get_attachment_metadata($data['logoId']) ?: [];

        $data['logo'] = apply_filters(
            'age_gate/logo/src',
            wp_get_attachment_url($data['logoId'])
        );

        $data['logoWidth'] = (int) apply_filters(
            'age_gate/logo/width',
            $imageMeta['width'] ?? 0
        );
        $data['logoHeight'] = apply_filters(
            'age_gate/logo/height',
            $imageMeta['height'] ?? 0
        );
        $data['logoAlt'] = apply_filters(
            'age_gate/logo/alt',
            get_post_meta($data['logoId'], '_wp_attachment_image_alt', true ) ?: '',
        );

        $data['backgroundImageId'] = $data['backgroundImage'] ?? false;
        $data['backgroundImage'] = $data['backgroundImageId'] ? wp_get_attachment_url($data['backgroundImage'] ?? null) : false;


        if ($data['stepped'] ?? false) {
            $data['dateFormat'] = 'YYYY MM DD';
        }


        // if (array_key_exists('debug', $_GET)) {
        //     dump($this->data);
        //     dump(array_diff(array_keys($data), array_keys($this->data)));
        //     dd($data);
        // }



        $this->data = $data;
    }

    public function __get($prop)
    {
        // _doing_it_wrong( $prop, 'Do not call properties directly', '3.0.0' );
        $c = $this->data[(new Convert($this->currentLanguage))->toCamel()][$prop] ?? $this->data[$this->currentLanguage][$prop] ?? $this->data[$prop] ?? false;


        return $c;
    }

    public function set($prop, $value)
    {
        $this->data[$prop] = $value;
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getCookieName()
    {
        return apply_filters('age_gate/cookie/name', $this->cookieName ?: 'age_gate');
    }
}
