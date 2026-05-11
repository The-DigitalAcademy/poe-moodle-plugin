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
global $DB;

echo html_writer::tag('h2', 'Export Course Portfolio By Cohort');

//  Get cohorts for this course
$cohorts = $DB->get_records_sql("
    SELECT c.id, c.name
    FROM {cohort} c
    JOIN {cohort_members} cm ON cm.cohortid = c.id
    JOIN {user_enrolments} ue ON ue.userid = cm.userid
    JOIN {enrol} e ON e.id = ue.enrolid
    WHERE e.courseid = ?
    GROUP BY c.id, c.name
", [$courseid]);

//Dropdown
$options = ['' => 'Select Cohort'];
foreach ($cohorts as $c) {
    $options[$c->id] = $c->name;
}

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => new moodle_url('/local/poe/export.php')
]);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'id',
    'value' => $courseid
]);

echo html_writer::select($options, 'cohortid', '', false);

//  Download button
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => 'Download ZIP',
    'class' => 'btn btn-primary'
]);

echo html_writer::end_tag('form');
//echo html_writer::tag('h2', 'Export Course Portfolio');

//$exporturl = new moodle_url('/local/poe/export.php', ['id' => $courseid]);

//echo html_writer::link($exporturl, 'Download ZIP', ['class' => 'btn btn-primary']);

echo $OUTPUT->footer();