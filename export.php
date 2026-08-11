<?php
use local_poe\task\poe_export_task;

require('../../config.php');

$groupid  = optional_param('group', 0, PARAM_INT);
$courseid = required_param('id', PARAM_INT);
require_login($courseid, true);

// Queue the export as a background task
$task = new poe_export_task();
$task->set_custom_data([
    'courseid' => $courseid,
    'userid'   => $USER->id,
    'groupid'  => $groupid,
]);
$task->set_userid($USER->id);
\core\task\manager::queue_adhoc_task($task, true); // true = only queue once if already pending

// Redirect user back to the course page with a success message
$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);

redirect(
    $courseurl,
    get_string('export_queued', 'local_poe'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);