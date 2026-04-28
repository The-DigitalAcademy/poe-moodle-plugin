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
     * 🔥 NEW: Get student submission (online text)
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
            JOIN {assign} a
                ON a.id = s.assignment
            LEFT JOIN {assignsubmission_onlinetext} at
                ON at.submission = s.id
            WHERE s.assignment = ?
              AND s.userid = ?
              AND s.status = 'submitted'
        ";

        return $DB->get_record_sql($sql, [$this->id, $userid]);
    }

    /**
     * 🔥 UPDATED: Render assignment + rubric + submission + metadata
     */
    public function to_html(poe_student $student = null, string $coursename = ''): string {

        // ✅ Styling (uniform output)
        $html = '
        <style>
            body { font-family: Arial, sans-serif; }
            .assignment-block { padding: 20px; }
            .metadata {
                background: #f5f5f5;
                padding: 15px;
                margin-bottom: 20px;
                border-left: 5px solid #0073e6;
            }
            .metadata p { margin: 5px 0; }
            .submission-content {
                padding: 15px;
                background: #ffffff;
                border: 1px solid #ddd;
            }
        </style>
        ';

        $html .= '<div class="assignment-block">';
        $html .= '<h2>' . format_string($this->name) . '</h2>';
        $html .= $this->intro;
        $html .= $this->activity;

        /**
         * ✅ EXISTING FEATURE: RUBRIC (UNCHANGED)
         */
        if (!empty($this->rubric)) {
            $criteria = [];

            foreach ($this->rubric as $row) {
                $criteria[$row['criteria']][] = [
                    'definition' => $row['definition'],
                    'score'      => (int) $row['score'],
                ];
            }

            $html .= '<h3>Rubric</h3>';
            $html .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%;">';

            $i = 0;
            foreach ($criteria as $criteria_name => $levels) {
                $bg = ($i % 2 === 0) ? '#f2f2f2' : '#ffffff';

                $html .= '<tr style="background-color:' . $bg . ';">';
                $html .= '<td><strong>' . $criteria_name . '</strong></td>';

                foreach ($levels as $level) {
                    $html .= '<td>';
                    $html .= $level['definition'] . '<br>';
                    $html .= '<span style="color: green;">' . $level['score'] . ' points</span>';
                    $html .= '</td>';
                }

                $html .= '</tr>';
                $i++;
            }

            $html .= '</table>';
        }

        /**
         * 🔥 NEW FEATURE: STUDENT SUBMISSION + METADATA
         */
        if ($student) {
            $submission = $this->get_student_submission($student->id);

            if ($submission) {

                $html .= '<div class="metadata">';
                $html .= '<p><strong>Student:</strong> ' . $student->name . '</p>';
                $html .= '<p><strong>Course:</strong> ' . $coursename . '</p>';
                $html .= '<p><strong>Module:</strong> ' . $this->get_course_section_name() . '</p>';
                $html .= '<p><strong>Assignment:</strong> ' . $this->name . '</p>';

                $html .= '<p><strong>Written:</strong> ' . userdate($submission->timecreated) . '</p>';
                $html .= '<p><strong>Submitted:</strong> ' . userdate($submission->timemodified) . '</p>';

                $html .= '<p><strong>Deadline:</strong> ' . ($submission->duedate ? userdate($submission->duedate) : 'N/A') . '</p>';
                $html .= '<p><strong>Venue:</strong> LMS (Online)</p>';
                $html .= '</div>';

                $html .= '<div class="submission-content">';
                $html .= '<h3>Submission</h3>';
                $html .= format_text($submission->onlinetext ?? '', FORMAT_HTML);
                $html .= '</div>';

            } else {
                $html .= '<p><em>No submission found for this student.</em></p>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * EXISTING HELPERS (UNCHANGED)
     */
    public function get_name(): string {
        return $this->name;
    }

    public function get_course_section_name(): string {
        return $this->course_module->get_course_section_name();
    }

    /**
     * ⚠️ DO NOT MODIFY — KEEP YOUR TEAMMATE'S ORIGINAL QUERY
     */
    public static function get_course_assignments(int $courseid): array {
        global $DB;

        $sql = "
            SELECT 
                a.id,
                a.name,
                a.intro,
                a.activity,
                cm.id as cmid
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            JOIN {assign} a ON a.id = cm.instance
            WHERE m.name = 'assign' AND cm.course = ?
        ";

        $records = $DB->get_records_sql($sql, [$courseid]);

        $assignments = [];

        foreach ($records as $record) {
            $cm = new poe_course_module($record->cmid);

            $assignment = new poe_assignment(
                $cm,
                $record->id,
                $record->name,
                $record->intro,
                $record->activity
            );

            $assignments[] = $assignment;
        }

        return $assignments;
    }
}