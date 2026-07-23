<?php
require('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$userid   = required_param('userid', PARAM_INT);

require_login($courseid, true);

// Only allow the owner or admins to download
if ($userid !== (int)$USER->id && !is_siteadmin()) {
    throw new \moodle_exception('nopermissions', 'error', '', 'download this export');
}

$context = context_course::instance($courseid);
$fs      = get_file_storage();

// Find the stored export file for this user
$files = $fs->get_area_files($context->id, 'local_poe', 'export', $userid, 'timecreated DESC', false);

if (empty($files)) {
    throw new \moodle_exception('exportnotfound', 'local_poe');
}

// Send the most recent file
$file = reset($files);
send_stored_file($file, 0, 0, true); // forcedownload = true