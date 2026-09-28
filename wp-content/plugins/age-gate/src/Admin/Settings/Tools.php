<?php

namespace AgeGate\Admin\Settings;

trait Tools
{
    protected function getToolsFields()
    {
        return [
            [
                'title' => 'Disable Age Gate',
                'subtitle' => '',
                'fields' => [
                    'disable_age_gate' => [
                        'type' => 'checkbox',
                        'label' => __('Disable Age Gate', 'age-gate'),
                        'default' => false,
                        'subtext' => __('Disable all Age Gates to users', 'age-gate'),
                    ],
                ]
            ],
            [
                'title' => 'Developer options',
                'subtitle' => '',
                'fields' => [

                    'feedback' => [
                        'type' => 'checkbox',
                        'label' => __('Send feedback', 'age-gate'),
                        'default' => false,
                        'subtext' => __('Occasionally send settings information to the developers', 'age-gate'),
                    ],
                ]
            ],
        ];
    }
}
