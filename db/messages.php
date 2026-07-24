<?php

// Defines the message provider used to notify users when their export is ready

defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'export_ready' => [
        'defaults' => [
            'popup'  => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
            'email'  => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
        ],
    ],
];