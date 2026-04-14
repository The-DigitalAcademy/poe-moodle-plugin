<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_quiz {
    protected poe_course_module $course_module;
    public int $id;
    public string $name;
    public string $intro;
    public $questions;

    public function __construct(poe_course_module $cm, int $id, $name, $intro) {
        $this->course_module = $cm;
        $this->id = $id;
        $this->name = $name;
        $this->intro = $intro;
    }

    public function to_html(): string {
        $html = '<h2>' . $this->name . '</h2>';
        $html .= $this->intro;
        // ...add quiz questions to html doc
        return  $html;
    }
    public function get_name():string {
        return $this->name;
    }

    public function get_course_section_name(): string {
        return $this->course_module->get_course_section_name();
    }

    static function get_course_quizzes(int $courseid): array {
        global $DB;
        $sql = "
            SELECT 
                q.id,
                q.name AS q_name,
                q.intro AS q_intro,
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
            JOIN {quiz} q
                ON q.id = cm.instance
            JOIN {course_sections} cs 
                ON cs.id = cm.section
            WHERE m.name = 'quiz' AND cm.course = ?
        ";
        $records = $DB->get_records_sql($sql, [$courseid]);

        $course_sections = [];
        $course_modules = [];
        $quizzes = [];

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
            $quiz = new poe_quiz($cm, $record->id, $record->q_name, $record->q_intro);
            array_push($quizzes, $quiz);
        }

        return $quizzes;

    }
}
