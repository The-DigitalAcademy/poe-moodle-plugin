<?php

// Defines the file serving capability for the export filearea

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/poe:downloadexport' => [
        'captype'      => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'student'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
];