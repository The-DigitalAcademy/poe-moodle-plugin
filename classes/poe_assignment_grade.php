<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment_grade {
    public int $assignment_id;
    public int $student_id;
    public float $grade;
    public float $maxgrade;
    public string $grader_name;
    public int $grading_time;
    public string $feedback;
    public string $grading_method; 
    public array $rubric_data;   

    public function __construct(
        int $assignment_id,
        int $student_id,
        float $grade,
        float $maxgrade,
        string $grader_name,
        int $grading_time,
        string $feedback,
        string $grading_method,
        array $rubric_data = []
    ) {
        $this->assignment_id  = $assignment_id;
        $this->student_id     = $student_id;
        $this->grade          = $grade;
        $this->maxgrade       = $maxgrade;
        $this->grader_name    = $grader_name;
        $this->grading_time   = $grading_time;
        $this->feedback       = $feedback;
        $this->grading_method = $grading_method;
        $this->rubric_data    = $rubric_data;
    }

    public function to_html(): string {
        $grading_time = userdate($this->grading_time);
        $method_label = match($this->grading_method) {
            'rubric' => 'Rubric',
            'guide'  => 'Marking Guide',
            default  => 'Direct Grading',
        };

        $html  = '<div class="grading-report" style="font-family: sans-serif; max-width: 900px; margin: 0 auto; padding: 24px;">';

        // Meta section
        $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom: 24px;">';
        $html .= '<tr><th style="text-align:left; padding: 6px 12px; background:#f0f0f0; width:200px;">Grader</th>';
        $html .= '<td style="padding: 6px 12px;">' . s($this->grader_name) . '</td></tr>';
        $html .= '<tr><th style="text-align:left; padding: 6px 12px; background:#f0f0f0;">Grading method</th>';
        $html .= '<td style="padding: 6px 12px;">' . $method_label . '</td></tr>';
        $html .= '<tr><th style="text-align:left; padding: 6px 12px; background:#f0f0f0;">Graded on</th>';
        $html .= '<td style="padding: 6px 12px;">' . $grading_time . '</td></tr>';
        $html .= '<tr><th style="text-align:left; padding: 6px 12px; background:#f0f0f0;">Grade</th>';
        $html .= '<td style="padding: 6px 12px;">' . $this->grade . ' / ' . $this->maxgrade . '</td></tr>';
        $html .= '</table>';

        // Feedback
        if (!empty($this->feedback)) {
            $html .= '<h3>Feedback</h3>';
            $html .= '<div style="padding: 12px; border-left: 4px solid #0073aa; background: #f9f9f9; margin-bottom: 24px;">';
            $html .= $this->feedback;
            $html .= '</div>';
        }

        // Rubric
        if ($this->grading_method === 'rubric' && !empty($this->rubric_data)) {
            $html .= $this->render_rubric();
        }

        // Marking guide
        if ($this->grading_method === 'guide' && !empty($this->rubric_data)) {
            $html .= $this->render_marking_guide();
        }

        $html .= '</div>';
        return $html;
    }

    protected function render_rubric(): string {
        $html  = '<h3>Rubric</h3>';
        $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom:24px;">';

        foreach ($this->rubric_data as $criterion) {
            // Criterion header row
            $html .= '<tr>';
            $html .= '<th colspan="' . count($criterion->levels) . '" style="text-align:left; padding:8px 12px; background:#003366; color:#fff;">';
            $html .= s($criterion->description);
            $html .= '</th>';
            $html .= '</tr>';

            // Level score header row
            $html .= '<tr>';
            foreach ($criterion->levels as $level) {
                $is_selected = ($level->id == $criterion->selected_level_id);
                $bg     = $is_selected ? '#d4edda' : '#f8f8f8';
                $border = $is_selected ? '3px solid #28a745' : '1px solid #ddd';
                $html .= '<th style="padding:6px 10px; background:' . $bg . '; border:' . $border . '; text-align:center; font-weight:normal;">';
                $html .= s($level->score) . ' pts';
                $html .= '</th>';
            }
            $html .= '</tr>';

            // Level definition row
            $html .= '<tr>';
            foreach ($criterion->levels as $level) {
                $is_selected    = ($level->id == $criterion->selected_level_id);
                $bg             = $is_selected ? '#d4edda' : '#ffffff';
                $border         = $is_selected ? '3px solid #28a745' : '1px solid #ddd';
                $selected_badge = $is_selected
                    ? ' <span style="background:#28a745;color:#fff;padding:2px 6px;border-radius:3px;font-size:11px;">Selected</span>'
                    : '';
                $html .= '<td style="padding:8px 10px; background:' . $bg . '; border:' . $border . '; vertical-align:top;">';
                $html .= format_text($level->definition, FORMAT_HTML) . $selected_badge;
                $html .= '</td>';
            }
            $html .= '</tr>';

            // Remark row
            if (!empty($criterion->remark)) {
                $html .= '<tr>';
                $html .= '<td colspan="' . count($criterion->levels) . '" style="padding:6px 12px; background:#fff8e1; border:1px solid #ddd; font-style:italic;">';
                $html .= '<strong>Remark:</strong> ' . s($criterion->remark);
                $html .= '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</table>';
        return $html;
    }

    protected function render_marking_guide(): string {
        $html  = '<h3>Marking Guide</h3>';
        $html .= '<table style="width:100%; border-collapse:collapse; margin-bottom:24px;">';
        $html .= '<tr style="background:#003366; color:#fff;">';
        $html .= '<th style="padding:8px 12px; text-align:left;">Criterion</th>';
        $html .= '<th style="padding:8px 12px; text-align:left;">Description</th>';
        $html .= '<th style="padding:8px 12px; text-align:center;">Score</th>';
        $html .= '<th style="padding:8px 12px; text-align:center;">Max</th>';
        $html .= '<th style="padding:8px 12px; text-align:left;">Remark</th>';
        $html .= '</tr>';

        foreach ($this->rubric_data as $i => $criterion) {
            $bg = $i % 2 === 0 ? '#ffffff' : '#f8f8f8';
            $html .= '<tr style="background:' . $bg . '; border-bottom:1px solid #ddd;">';
            $html .= '<td style="padding:8px 12px; font-weight:bold;">' . s($criterion->description) . '</td>';
            $html .= '<td style="padding:8px 12px;">' . format_text($criterion->descriptionmarkers, FORMAT_HTML) . '</td>';
            $html .= '<td style="padding:8px 12px; text-align:center;">' . $criterion->score . '</td>';
            $html .= '<td style="padding:8px 12px; text-align:center;">' . $criterion->maxscore . '</td>';
            $html .= '<td style="padding:8px 12px; font-style:italic;">' . s($criterion->remark) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Fetch the grade for a student on an assignment.
     * Returns null if the assignment has not been graded.
     */
    static function get_for_student(int $assignmentid, int $studentid, float $maxgrade, array $assignment_rubric = []): ?self {
        global $DB;

        $grade = $DB->get_record_sql("
            SELECT *
            FROM {assign_grades}
            WHERE assignment = ?
              AND userid = ?
            ORDER BY timemodified DESC
            LIMIT 1
        ", [$assignmentid, $studentid]);

        if (!$grade || $grade->grade < 0) {
            return null;
        }

        // Grader name
        $grader = $DB->get_record('user', ['id' => $grade->grader], 'id, firstname, lastname, firstnamephonetic, lastnamephonetic, middlename, alternatename');
        $grader_name = $grader ? fullname($grader) : 'Unknown';

        // Feedback comment
        $feedback_record = $DB->get_record_sql("
            SELECT *
            FROM {assignfeedback_comments}
            WHERE assignment = ?
              AND grade = ?
            ORDER BY id DESC
            LIMIT 1
        ", [$assignmentid, $grade->id]);
        $feedback = $feedback_record ? format_text($feedback_record->commenttext, FORMAT_HTML) : '';

        // Detect grading method
        $grading_area = $DB->get_record_sql("
            SELECT ga.*, ga.activemethod
            FROM {grading_areas} ga
            JOIN {context} ctx ON ctx.id = ga.contextid
            JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = 70
            WHERE cm.instance = ? AND ga.component = 'mod_assign' AND ga.areaname = 'submissions'
        ", [$assignmentid]);

        $grading_method = 'simple';
        $rubric_data    = [];

        if ($grading_area && !empty($grading_area->activemethod)) {
            $grading_method = $grading_area->activemethod;

            $definition = $DB->get_record('grading_definitions', [
                'areaid' => $grading_area->id,
                'status' => 20,
            ]);

            if ($definition) {
                if ($grading_method === 'rubric') {
                    $rubric_data = self::load_rubric_fillings_with_definition(
                        $definition->id,
                        $grade->id,
                        $assignment_rubric
                    );
                } elseif ($grading_method === 'guide') {
                    $rubric_data = self::load_guide_fillings($definition->id, $grade->id);
                }
            }
        }

        return new self(
            $assignmentid,
            $studentid,
            round((float)$grade->grade, 2),
            $maxgrade,
            $grader_name,
            (int)$grade->timemodified,
            $feedback,
            $grading_method,
            $rubric_data
        );
    }

    protected static function load_rubric_fillings_with_definition(int $definitionid, int $gradeid, array $assignment_rubric): array {
        global $DB;

        // Get the most recent active grading instance for this grade
        $instance = $DB->get_record_sql("
            SELECT *
            FROM {grading_instances}
            WHERE definitionid = ?
              AND itemid = ?
              AND status = 1
            ORDER BY timemodified DESC
            LIMIT 1
        ", [$definitionid, $gradeid]);

        if (!$instance) {
            return [];
        }

        // Load the student's level selections and remarks
        $fillings = $DB->get_records('gradingform_rubric_fillings', ['instanceid' => $instance->id]);
        $fillings_by_criterion = [];
        foreach ($fillings as $filling) {
            $fillings_by_criterion[$filling->criterionid] = $filling;
        }

        // Load criteria and levels from the definition
        $criteria = $DB->get_records('gradingform_rubric_criteria',
            ['definitionid' => $definitionid],
            'sortorder ASC'
        );

        $result = [];
        foreach ($criteria as $criterion) {
            $levels  = $DB->get_records('gradingform_rubric_levels',
                ['criterionid' => $criterion->id],
                'score ASC'
            );
            $filling = $fillings_by_criterion[$criterion->id] ?? null;

            $criterion_obj = new \stdClass();
            $criterion_obj->description       = $criterion->description;
            $criterion_obj->levels            = array_values($levels);
            $criterion_obj->selected_level_id = $filling->levelid ?? null;
            $criterion_obj->remark            = $filling->remark ?? '';

            $result[] = $criterion_obj;
        }

        return $result;
    }

    protected static function load_guide_fillings(int $definitionid, int $gradeid): array {
        global $DB;

        // Get the most recent active grading instance for this grade
        $instance = $DB->get_record_sql("
            SELECT *
            FROM {grading_instances}
            WHERE definitionid = ?
              AND itemid = ?
              AND status = 1
            ORDER BY timemodified DESC
            LIMIT 1
        ", [$definitionid, $gradeid]);

        if (!$instance) {
            return [];
        }

        $criteria = $DB->get_records('gradingform_guide_criteria',
            ['definitionid' => $definitionid],
            'sortorder ASC'
        );

        $fillings = $DB->get_records('gradingform_guide_fillings', ['instanceid' => $instance->id]);
        $fillings_by_criterion = [];
        foreach ($fillings as $filling) {
            $fillings_by_criterion[$filling->criterionid] = $filling;
        }

        $result = [];
        foreach ($criteria as $criterion) {
            $filling = $fillings_by_criterion[$criterion->id] ?? null;

            $criterion_obj = new \stdClass();
            $criterion_obj->description        = $criterion->shortname;
            $criterion_obj->descriptionmarkers = $criterion->descriptionmarkers ?? '';
            $criterion_obj->maxscore           = $criterion->maxscore;
            $criterion_obj->score              = $filling->score ?? 0;
            $criterion_obj->remark             = $filling->remark ?? '';

            $result[] = $criterion_obj;
        }

        return $result;
    }
}