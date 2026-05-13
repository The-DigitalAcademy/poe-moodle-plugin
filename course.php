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

global $DB;

// Pull Moodle Groups ( as Cohorts)
$groups = $DB->get_records_sql("
    SELECT id, name
    FROM {groups}
    WHERE courseid = ?
    ORDER BY name ASC
", [$courseid]);

// Dropdown options
$options = [0 => 'Select Group'];

foreach ($groups as $group) {
    $options[$group->id] = $group->name;
}

// FORM
echo html_writer::start_tag('form', [
    'method' => 'get',
   'action' => $CFG->wwwroot . '/local/poe/export.php'
]);

// Course ID
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'id',
    'value' => $courseid
]);

// Dropdown
echo html_writer::select($options, 'group', '', false);

// Space
echo '&nbsp;&nbsp;&nbsp;';

// Download button
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => 'Download ZIP',
    'class' => 'btn btn-primary'
]);

echo html_writer::end_tag('form');

echo $OUTPUT->footer();