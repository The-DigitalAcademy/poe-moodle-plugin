<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment {
    protected poe_course_module $course_module;
    protected int $id;
    protected string $name;
    protected string $intro;
    protected string $activity;
    protected $rubric;

    public function __construct(poe_course_module $cm, $id, $name, $intro, $activity) {
        $this->course_module = $cm;
        $this->id = $id;
        $this->name = $name;
        $this->intro = $intro;
        $this->activity = $activity;
    }

    public function to_html(): string {
        $html = '<h2>' . $this->name . '</h2>';
        $html .= $this->intro;
        $html .= $this->activity;
        // add rubric to html doc
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
                a.id,
                a.name AS a_name,
                a.intro AS a_intro,
                a.activity AS a_activity,
                cs.id AS cs_id,
                cs.name AS cs_name,
                cs.section AS cs_number,
                cs.summary AS cs_summary,
                cs.sequence AS cs_cm_sequence,
                cm.id AS cm_id,
                m.name AS cm_module
            FROM {course_modules} cm
            JOIN {modules} m 
                ON m.id = cm.module
            JOIN {assign} a
                ON a.id = cm.instance
            JOIN {course_sections} cs 
                ON cs.id = cm.section
            WHERE m.name = 'assign' AND cm.course = ?
        ";

        $records = $DB->get_records_sql($sql, [$courseid]);

        $course_sections = [];
        $course_modules = [];
        $assignments = [];

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
            $cm = $course_modules[$record->cm_id];
            $assignment = new poe_assignment($cm, $record->id, $record->a_name, $record->a_intro, $record->a_activity);
            array_push($assignments, $assignment);
        }

        return $assignments;
    }
}
