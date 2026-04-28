<?php

use local_poe\poe_course;

require('../../config.php');

$courseid = required_param('id', PARAM_INT);
require_login($courseid, true);

$course = new poe_course($courseid);

// create temp directory
$tempzip = tempnam($CFG->tempdir . '/', 'poe');

$html_guide = $course->get_html_guide();
$filelist = [];
$fs = get_file_storage();

// add generic resources to all students' directories
foreach ($course->students as $student) {

    $studentname = $student->get_fullname();

    // LEARNER GUIDE 
    $filelist["/{$studentname}/learner_guide.html"] = [
        $html_guide
    ];

    /**
     * ASSIGNMENTS (with metadata)
     */
    foreach ($course->assignments as $assignment) {
        $filelist["/{$studentname}/{$assignment->get_course_section_name()}/{$assignment->get_name()}/assignment.html"] = [
            $assignment->to_html($student, $course->name)
        ];
    }

    /**
     * QUIZZES
     */
    foreach ($course->quizzes as $quiz) {
        $filelist["/{$studentname}/{$quiz->get_course_section_name()}/{$quiz->get_name()}/quiz.html"] = [
            $quiz->to_html()
        ];
    }
}

/**
 * 🔥 UPDATED: Assignment submissions (HTML + files)
 */
foreach ($course->get_assignment_submissions() as $submission) {

    $studentname = $submission->get_student_fullname();

    $basepath = "/{$studentname}/{$submission->get_course_section_name()}/{$submission->get_assignment_name()}";

    // 🔥 CLEAN HTML EXPORT (UPDATED)
    if ($submission->has_onlinetext()) {

        $filelist["{$basepath}/submission-{$submission->get_attemptnumber()}/onlinetext.html"] = [
            $submission->to_html() //  KEY FIX
        ];
    }

    // 🔥 FILE EXPORT (UNCHANGED)
    if ($submission->has_file()) {

        $stored_file = $fs->get_file_by_id($submission->get_fileid());

        if ($stored_file) {
            $filelist["{$basepath}/submission/{$stored_file->get_filename()}"] = $stored_file;
        }
    }
}

// zip files
$zipper = new zip_packer();
$zipper->archive_to_pathname($filelist, $tempzip);

// send temp file
send_temp_file($tempzip, "{$course->name}.zip");
die();