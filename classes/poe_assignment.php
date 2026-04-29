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

    public function to_html(): string {
        $html = '<h2>' . $this->name . '</h2>';
        $html .= $this->intro;
        $html .= $this->activity;

        // rubric
        if (!empty($this->rubric)) {
            // group levels by criteria
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
                // criteria name as first cell
                $html .= '<td><strong>' . $criteria_name . '</strong></td>';
                // each level as its own cell
                foreach ($levels as $level) {
                    $html .= '<td>';
                    $html .= $level['definition'] . '<br>';
                    $html .= '<text style="color: green;">' . $level['score'] . ' points' . '</text>';
                    $html .= '</td>';
                }
                $html .= '</tr>';
                $i++;
            }

            $html .= '</table>';
        }

        return  $html;
    }

    public function get_name():string {
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
