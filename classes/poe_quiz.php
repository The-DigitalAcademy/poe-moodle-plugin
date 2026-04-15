<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_quiz {
    public int $id;
    public string $section;
    public string $name;
    public string $intro;

    /**
     * @var poe_quiz_question[]
     */
    public array $questions;

    public function __construct(string $section, int $id, $name, $intro) {
        $this->section = $section;
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
}