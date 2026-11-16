<?php

defined('MOODLE_INTERNAL') || die();

function local_poe_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {

    // Only show the nav item to users who can export
    if (!has_capability('local/poe:export', $context)) {
        return;
    }

    $url = new moodle_url('/local/poe/course.php', ['id' => $course->id]);

    $navigation->add(
        'Portfolio of Evidence',
        $url,
        navigation_node::TYPE_COURSE,
        null,
        'Portfolio of Evidence'
    );
}