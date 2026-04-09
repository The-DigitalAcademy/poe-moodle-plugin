<?php

use local_poe\poe_course;

require('../../config.php');

$courseid = required_param('id', PARAM_INT);
require_login($courseid, true);
$course = new poe_course($courseid);

// create temp directory
$tempzip = tempnam($CFG->tempdir . '/', 'poe');
// $filelist = [];

$html_guide = $course->get_html_guide();

foreach ($course->students as $student) {

    // add learner guide to each student's directory
    $filelist["/{$student->name}/learner_guide.html"] = array($html_guide);

    // add each assignment to each student's directory
    foreach ($course->assignments as $assignment) {
        $filelist["/{$student->name}/{$assignment->section}/{$assignment->name}/assignment.html"] = array($assignment->to_html());
    }

    // add each quiz to each student's directory
    foreach ($course->quizzes as $quiz) {
        $filelist["/{$student->name}/{$quiz->section}/{$quiz->name}/quiz.html"] = array($quiz->to_html());
    }
}

// zip files
$zipper = new zip_packer();
$zipper->archive_to_pathname($filelist, $tempzip);

// send temp file to user, forced download
send_temp_file($tempzip, "{$course->name}.zip");
die();