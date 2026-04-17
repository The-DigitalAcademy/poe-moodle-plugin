<?php

require('../../config.php');

$courseid = required_param('id', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);

require_login($courseid, true);
$PAGE->set_url('/local/poe/course.php', ['id' => $courseid]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title("POE | " . $course->fullname);
$PAGE->set_heading($course->fullname);


echo $OUTPUT->header();

echo html_writer::tag('h2', 'Export Course Portfolio');

$exporturl = new moodle_url('/local/poe/export.php', ['id' => $courseid]);

echo html_writer::link($exporturl, 'Download ZIP', ['class' => 'btn btn-primary']);

echo $OUTPUT->footer();