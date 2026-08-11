<?php
namespace local_poe\task;
 
defined('MOODLE_INTERNAL') || die();
 
class poe_export_task extends \core\task\adhoc_task {
 
    public function execute() {
        global $CFG;
 
        $data     = $this->get_custom_data();
        $courseid = $data->courseid;
        $userid   = $data->userid;
        $groupid  = $data->groupid ?? 0;
 
        mtrace("POE Export: starting for course {$courseid}, user {$userid}");
 
        $course  = new \local_poe\poe_course($courseid, $groupid);
        $tempzip = tempnam($CFG->tempdir . '/', 'poe');
 
        $html_guide = $course->get_html_guide();
        $pdf_guide  = $course->get_pdf_guide($html_guide);
        $filelist   = [];
        $fs         = get_file_storage();
 
        // add generic resources to all students' directories
        foreach ($course->students as $student) {
            $studentname = $student->get_fullname();
 
            // LEARNER GUIDE
            $filelist["/{$studentname}/learner_guide.html"] = [$html_guide];
            $filelist["/{$studentname}/learner_guide.pdf"]  = [$pdf_guide];
 
            /**
             * ASSIGNMENTS (with metadata)
             */
            foreach ($course->assignments as $assignment) {
                $filelist["/{$student->get_fullname()}/{$assignment->get_course_section_name()}/{$assignment->get_name()}/assignment.html"] = array($assignment->to_html());
 
                $grade = \local_poe\poe_assignment_grade::get_for_student(
                    $assignment->get_id(),
                    $student->get_id(),
                    $assignment->get_maxgrade(),
                    $assignment->rubric
                );
                if ($grade !== null) {
                    $filelist["/{$student->get_fullname()}/{$assignment->get_course_section_name()}/{$assignment->get_name()}/grading.html"] = array($grade->to_html());
                }
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
         * Assignment submissions (HTML + files)
         */
        foreach ($course->get_assignment_submissions() as $submission) {
            $studentname = $submission->get_student_fullname();
            $basepath    = "/{$studentname}/{$submission->get_course_section_name()}/{$submission->get_assignment_name()}";
 
            if ($submission->has_onlinetext()) {
                $filelist["{$basepath}/submission-{$submission->get_attemptnumber()}/onlinetext.html"] = [
                    $submission->to_html()
                ];
            }
 
            if ($submission->has_file()) {
                $stored_file = $fs->get_file_by_id($submission->get_fileid());
                if ($stored_file) {
                    $filelist["{$basepath}/submission/{$stored_file->get_filename()}"] = $stored_file;
                }
            }
        }
 
        // add each quiz attempt to the respective student's directory
        foreach ($course->get_quiz_attempts() as $qattempt) {
            $filelist["/{$qattempt->get_username()}/{$qattempt->get_sectionname()}/{$qattempt->get_quizname()}/attempt-{$qattempt->get_attemptnumber()}.html"] = array($qattempt->to_html());
        }
 
        // zip files
        $zipper = new \zip_packer();
        $zipper->archive_to_pathname($filelist, $tempzip);
 
        // Save the zip into Moodle's file storage so the user can download it
        $context  = \context_course::instance($courseid);
        $filename = clean_filename($course->name) . '_' . date('Ymd_His') . '.zip';
 
        // Delete any previous export for this user + course to avoid clutter
        $fs->delete_area_files($context->id, 'local_poe', 'export', $userid);
 
        $file_record = [
            'contextid' => $context->id,
            'component' => 'local_poe',
            'filearea'  => 'export',
            'itemid'    => $userid,
            'filepath'  => '/',
            'filename'  => $filename,
        ];
        $fs->create_file_from_pathname($file_record, $tempzip);
 
        // Clean up temp file
        unlink($tempzip);
 
        // Notify the user with a download link
        $downloadurl = new \moodle_url('/local/poe/download.php', [
            'courseid' => $courseid,
            'userid'   => $userid,
        ]);
 
        $message                    = new \core\message\message();
        $message->component         = 'local_poe';
        $message->name              = 'export_ready';
        $message->userfrom          = \core_user::get_noreply_user();
        $message->userto            = \core_user::get_user($userid);
        $message->subject           = "Your POE export is ready: {$course->name}";
        $message->fullmessage       = "Your export for {$course->name} is ready. Download it here: {$downloadurl->out(false)}";
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml   = "<p>Your export for <strong>{$course->name}</strong> is ready.</p><p><a href='{$downloadurl->out(false)}'>Download ZIP</a></p>";
        $message->smallmessage      = 'Your POE export is ready to download.';
        $message->notification      = 1;
        $message->contexturl        = $downloadurl->out(false);
        $message->contexturlname    = 'Download Export';
 
        try {
            message_send($message);
        } catch (\Exception $e) {
            mtrace("Warning: notification send error: " . $e->getMessage());
        }
 
        mtrace("POE Export: completed for course {$courseid}, user {$userid}");
    }
}