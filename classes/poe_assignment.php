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
    protected float $maxgrade;

    public function __construct(poe_course_module $cm, $id, $name, $intro, $activity, float $maxgrade = 0) {
        $this->course_module = $cm;
        $this->id = $id;
        $this->name = $name;
        $this->intro = $intro;
        $this->activity = $activity;
        $this->maxgrade = $maxgrade;
    }

    public function get_id(): int {
        return $this->id;
    }

    public function get_maxgrade(): float {
        return $this->maxgrade;
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

    /**
     * Summary of get_course_assignments
     * @return poe_assignment[]
     */
    static function get_course_assignments(int $courseid): array {
        global $DB;
        $sql = "
            SELECT 
                grl.id AS id,
                a.id AS assignment_id,
                a.name AS a_name,
                a.intro AS a_intro,
                a.activity AS a_activity,
                a.grade AS a_maxgrade,
                cs.id AS cs_id,
                cs.name AS cs_name,
                cs.section AS cs_number,
                cs.summary AS cs_summary,
                cs.sequence AS cs_cm_sequence,
                cm.id AS cm_id,
                m.name AS cm_module,
                grc.id AS criteria_id,
                grc.description AS criteria_description,
                grc.sortorder AS criteria_sortorder,
                grl.score,
                grl.definition AS level_definition
            FROM {course_modules} cm
            JOIN {modules} m 
                ON m.id = cm.module
            JOIN {assign} a
                ON a.id = cm.instance
            JOIN {course_sections} cs 
                ON cs.id = cm.section
            LEFT JOIN {context} ctx
                ON ctx.instanceid = cm.id AND ctx.contextlevel = 70
            LEFT JOIN {grading_areas} ga
                ON ga.contextid = ctx.id AND ga.component = 'mod_assign'
            LEFT JOIN {grading_definitions} gd
                ON gd.areaid = ga.id AND gd.method = 'rubric'
            LEFT JOIN {gradingform_rubric_criteria} grc
                ON grc.definitionid = gd.id
            LEFT JOIN {gradingform_rubric_levels} grl
                ON grl.criterionid = grc.id
            WHERE m.name = 'assign' AND cm.course = ?
            ORDER BY a.id, grc.sortorder, grl.score
        ";

        $records = $DB->get_records_sql($sql, [$courseid]);

        $course_sections = [];
        $course_modules  = [];
        $assignments     = [];

        foreach ($records as $record) {
            // create course section
            if (empty($course_sections[$record->cs_id])) {
                $course_sections[$record->cs_id] = new poe_course_section($record->cs_id, $record->cs_name, $record->cs_number, $record->cs_summary, $record->cs_cm_sequence);
            }

            //  create course module
            if (empty($course_modules[$record->cm_id])) {
                $cs = $course_sections[$record->cs_id];
                $course_modules[$record->cm_id] = new poe_course_module($cs, $record->cm_id, $record->cm_module);
            }

            //  create assignment
            if (empty($assignments[$record->assignment_id])) {
                $cm = $course_modules[$record->cm_id];
                $assignments[$record->assignment_id] = new poe_assignment(
                    $cm,
                    $record->assignment_id,
                    $record->a_name,
                    $record->a_intro,
                    $record->a_activity,
                    (float)$record->a_maxgrade
                );
            }

            // append rubric row if it exists
            if (!empty($record->criteria_id)) {
                $assignments[$record->assignment_id]->rubric[] = [
                    'criteria'   => $record->criteria_description,
                    'score'      => $record->score,
                    'definition' => $record->level_definition,
                ];
            }
        }

        return array_values($assignments);
    }
}
