<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment {
    protected poe_course_module $course_module;
    protected int $id;
    protected string $name;
    protected string $intro;
    protected string $activity;

    public array $rubric = [];

    public function __construct(poe_course_module $cm, $id, $name, $intro, $activity) {
        $this->course_module = $cm;
        $this->id = $id;
        $this->name = $name ?? '';
        $this->intro = $intro ?? '';
        $this->activity = $activity ?? '';
    }

    /**
     * Get student submission (safe null handling)
     */
    public function get_student_submission(int $userid): ?\stdClass {
        global $DB;

        $sql = "
            SELECT 
                s.id,
                s.userid,
                s.timecreated,
                s.timemodified,
                a.duedate,
                at.onlinetext
            FROM {assign_submission} s
            JOIN {assign} a ON a.id = s.assignment
            LEFT JOIN {assignsubmission_onlinetext} at ON at.submission = s.id
            WHERE s.assignment = ?
              AND s.userid = ?
              AND s.status = 'submitted'
        ";

        $record = $DB->get_record_sql($sql, [$this->id, $userid]);

        return $record ?: null;
    }

    /**
     * Render assignment HTML (final clean version)
     */
    public function to_html(poe_student $student = null, string $coursename = ''): string {

        // 🔥 Centralized styling
        $html = poe_renderer::get_styles();

        $html .= '<div class="assignment-block">';
        $html .= '<h2>' . format_string($this->name) . '</h2>';

        // 🔥 Safe HTML rendering
        $html .= '<div class="assignment-intro">' . format_text($this->intro, FORMAT_HTML) . '</div>';
        $html .= '<div class="assignment-activity">' . format_text($this->activity, FORMAT_HTML) . '</div>';

        /**
         * 🔥 RUBRIC
         */
        if (!empty($this->rubric)) {

            $criteria = [];

            foreach ($this->rubric as $row) {
                $criteria[$row['criteria']][] = [
                    'definition' => $row['definition'],
                    'score' => (int)$row['score'],
                ];
            }

            $html .= '<h3>Rubric</h3>';
            $html .= '<table class="rubric-table">';

            foreach ($criteria as $criteria_name => $levels) {

                $html .= '<tr>';
                $html .= '<td><strong>' . $criteria_name . '</strong></td>';

                foreach ($levels as $level) {
                    $html .= '<td>';
                    $html .= $level['definition'];
                    $html .= '<br><span class="points">' . $level['score'] . ' pts</span>';
                    $html .= '</td>';
                }

                $html .= '</tr>';
            }

            $html .= '</table>';
        }

        /**
         * 🔥 SUBMISSION + METADATA
         */
        if ($student) {

            $submission = $this->get_student_submission($student->get_id());

            if ($submission) {

                $html .= '<div class="metadata">';
                $html .= '<h3>Submission Details</h3>';

                $html .= '<p><strong>Student:</strong> ' . $student->get_fullname() . '</p>';
                $html .= '<p><strong>Course:</strong> ' . $coursename . '</p>';
                $html .= '<p><strong>Module:</strong> ' . $this->get_course_section_name() . '</p>';
                $html .= '<p><strong>Assignment:</strong> ' . $this->name . '</p>';
                $html .= '<p><strong>Status:</strong> Submitted</p>';

                $html .= '<p><strong>Written:</strong> ' . userdate($submission->timecreated) . '</p>';
                $html .= '<p><strong>Submitted:</strong> ' . userdate($submission->timemodified) . '</p>';
                $html .= '<p><strong>Deadline:</strong> ' . ($submission->duedate ? userdate($submission->duedate) : 'N/A') . '</p>';
                $html .= '<p><strong>Venue:</strong> LMS (Online)</p>';

                $html .= '</div>';

                $html .= '<div class="submission-content">';
                $html .= '<h3>Submission</h3>';

                $cleanSubmission = format_text($submission->onlinetext ?? '', FORMAT_HTML);
                $html .= $cleanSubmission ?: '<em>No content submitted</em>';

                $html .= '</div>';

            } else {
                $html .= '<p><em>No submission found for this student.</em></p>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_course_section_name(): string {
        return $this->course_module->get_course_section_name();
    }
}