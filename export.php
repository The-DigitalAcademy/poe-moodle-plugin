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
$filelist = [];
$fs = get_file_storage();

// add generic resources to all students' directories
foreach ($course->students as $student) {
    // LEARNER GUIDE 
    $filelist["/{$student->get_fullname()}/learner_guide.html"] = array($html_guide);

    // ASSIGNMENT
    foreach ($course->assignments as $assignment) {
        $filelist["/{$student->get_fullname()}/{$assignment->get_course_section_name()}/{$assignment->get_name()}/assignment.html"] = array($assignment->to_html());
    }
    // QUIZ
    foreach ($course->quizzes as $quiz) {
        $filelist["/{$student->get_fullname()}/{$quiz->get_course_section_name()}/{$quiz->get_name()}/quiz.html"] = array($quiz->to_html());
    }
}

// add each assignment submission to the respective student's directory
foreach ($course->get_assignment_submissions() as $submission) {
    if ($submission->has_onlinetext()) {
        $filelist["/{$submission->get_student_fullname()}/{$submission->get_course_section_name()}/{$submission->get_assignment_name()}/submission-{$submission->get_attemptnumber()}/onlinetext.html"] = array($submission->get_onlinetext());
    }
    if ($submission->has_file()) {
        $stored_file = $fs->get_file_by_id($submission->get_fileid());
        $filelist["/{$submission->get_student_fullname()}/{$submission->get_course_section_name()}/{$submission->get_assignment_name()}/submission/{$stored_file->get_filename()}"] = $stored_file;
    }
}

// zip files
$zipper = new zip_packer();
$zipper->archive_to_pathname($filelist, $tempzip);

// send temp file to user, forced download
send_temp_file($tempzip, "{$course->name}.zip");
die();