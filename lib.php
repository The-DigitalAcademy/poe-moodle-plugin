<?php

defined('MOODLE_INTERNAL') || die();

function local_poe_extend_navigation_course(
    navigation_node $navigation,
    stdClass $course,
    context_course $context
) {

    if (
        has_capability('moodle/course:update', $context)
        || has_capability('moodle/site:config', $context)
    ) {

        $url = new moodle_url(
            '/local/poe/course.php',
            ['id' => $course->id]
        );

        $navigation->add(
            'Portfolio of Evidence',
            $url
        );
    }
}