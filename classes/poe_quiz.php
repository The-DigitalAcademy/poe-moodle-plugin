<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_quiz {
    protected poe_course_module $course_module;
    public int $id;
    public string $name;
    public string $intro;

    /**
     * @var poe_quiz_question[]
     */
    public array $questions;

    public function __construct(poe_course_module $cm, int $id, $name, $intro) {
        $this->course_module = $cm;
        $this->id = $id;
        $this->name = $name;
        $this->intro = $intro;
        $this->questions = [];

        $this->load_questions();
    }

    protected function load_questions(): void {
        global $DB;

        $sql = "
    SELECT
        q.id,
        q.name,
        q.questiontext,
        q.qtype,
        qs.maxmark
    FROM {quiz_slots} qs
    JOIN {question_references} qr
        ON qr.itemid = qs.id
    JOIN {question_bank_entries} qbe
        ON qbe.id = qr.questionbankentryid
    JOIN {question_versions} qv
        ON qv.questionbankentryid = qbe.id
    JOIN {question} q
        ON q.id = qv.questionid
    WHERE qs.quizid = ?
      AND qr.component = 'mod_quiz'
      AND qr.questionarea = 'slot'
    ORDER BY qs.slot ASC, qv.version DESC
";

        $records = $DB->get_records_sql($sql, [$this->id]);

        foreach ($records as $record) {
            $answers = [];

            // Load answer options for supported question types
            if (in_array($record->qtype, ['multichoice', 'truefalse', 'shortanswer'])) {

                $answerrecords = $DB->get_records(
                    'question_answers',
                    ['question' => $record->id],
                    'id ASC',
                    'id, answer'
                );

                foreach ($answerrecords as $answer) {
                    $answers[] = format_text($answer->answer, FORMAT_HTML);
                }
            }

            $this->questions[] = new poe_quiz_question(
                $record->id,
                $record->name,
                format_text($record->questiontext, FORMAT_HTML),
                $record->qtype,
                (float)$record->maxmark,
                $answers
            );
        }
    }

    public function to_html(): string {
        $html = '<h2>' . format_string($this->name) . '</h2>';
        $html .= $this->intro;

        if (!empty($this->questions)) {
            $html .= '<h3>Questions</h3>';

            foreach ($this->questions as $index => $question) {
                $html .= $question->to_html($index + 1);
            }
        } else {
            $html .= '<p><em>No questions found for this quiz.</em></p>';
        }

        return $html;
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
