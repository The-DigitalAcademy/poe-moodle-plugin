<?php
namespace local_poe;

use stdClass;

defined('MOODLE_INTERNAL') || die();

class poe_quiz_question_attempt {
    protected int $usageid;
    protected string $slot;
    protected string $questiontext;
    /**
     * @var stdClass[] object{answer: string, feedback: string, fraction: float}[]
     */
    protected array $answers;
    protected string|null $response;
    protected string $rightanswer;
    protected float $finalgrade;
    protected float $maxmark;

    public function __construct(int $usageid, int $slot, string $questiontext, array $answers, string|null $response, string $rightanswer, float $maxmark, float $finalgrade) {
        $this->usageid = $usageid;
        $this->slot = $slot;
        $this->questiontext = $questiontext;
        $this->response = $response;
        $this->answers = $answers;
        $this->rightanswer = $rightanswer;
        $this->maxmark = $maxmark;
        $this->finalgrade = $finalgrade;
    }

    public function get_usageid(): int {
        return $this->usageid;
    }
    public function get_maxmark(): float {
        return $this->maxmark;
    }

    /**
     * @param int $courseid
     * @return poe_quiz_question_attempt[]
     */
    static function get_question_attempts(int $courseid): array {
        global $DB;
        $answer_records = $DB->get_records('question_answers');
        $qa_sql = "
            SELECT 
                qa.id, qa.questionid, qa.questionusageid, qa.slot, qa.questionsummary, qa.responsesummary, qa.rightanswer, qa.maxmark,
                qa.maxmark * qas.fraction AS finalgrade,
                q.questiontext
            FROM {question_attempts} qa
            JOIN {question} q 
	            ON q.id = qa.questionid
            JOIN {question_attempt_steps} qas
                ON qas.questionattemptid = qa.id AND qas.fraction IS NOT NULL
        ";
        $qa_records = $DB->get_records_sql($qa_sql);

        $question_attempts = [];

        foreach ($qa_records as $record) {
            $answers_filtered = array_filter($answer_records, fn($val) => $val->question == $record->questionid);
            $answers = array_map(function($val) {
                    $answer = new stdClass();
                    $answer->answer = $val->answer;
                    $answer->feedback = $val->feedback;
                    $answer->fraction = $val->fraction;
                    return $answer;
                }, 
                $answers_filtered);
            $qa = new poe_quiz_question_attempt($record->questionusageid, $record->slot, $record->questiontext, $answers, $record->responsesummary, $record->rightanswer, $record->maxmark, $record->finalgrade);

            array_push($question_attempts, $qa);
        }

        return $question_attempts;
    }

    public function to_html(): string {
        $html = '
        <div style="
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.8em;
        ">
            <div style="
                border: 1px solid #cad0d7;
                padding: 0.5rem;
                border-radius: 2px;
                width: 7em;
                flex-shrink: 0;
                background: #f8f9fa
            ">
                <h3>Question '.$this->slot.'</h3>
                <p>Mark ' . $this->finalgrade . ' out of ' . $this->maxmark . '
            </div>
            <div style="width:100%">
                <div style="
                        color: #001a1e;
                        border: 1px solid #b3d9e0;
                        background: #cce6ea;
                        border-radius: 0.5rem;
                        width: 100%;
                        padding: 1em;
                        margin-bottom: 1em">
                    '.$this->questiontext.'
                    <p>Select one:</p>
            ';
        foreach ($this->answers as $answer) {
            $checked = "";
            $check_icon = '
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#306e2d" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
                </svg>';
            $x_icon = '
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#dc3545" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                </svg>
            ';

            $icon = "";
            if ($answer->answer == $this->response) {
                $checked = "checked";
                if ($answer->fraction == 1) {
                    $icon = $check_icon;
                } else {
                    $icon = $x_icon;
                }
            }
            $feedbackmsg = !!$checked ? '<span style="background:#fff3bf; padding:0 0.7em;display:inline">' . $answer->feedback . '</span>' : "";
   
            $html .= '
                <label style="display:flex;align-items:center;gap:0.3rem">
                    <input '. $checked . ' disabled type="radio">
                    <span style="flex-shrink:0">'.$answer->answer. '</span>' . 
                    $icon . 
                    $feedbackmsg. '
                </label>';
        }
        $html .= '</div>';

        // correct answer
        $html .= '
        <div style="
            color: #a67736; 
            border: 1px solid #fbe6ca;
            background: #fcefdc;
            padding: 1em;
            width: 100%;
            margin-bottom: 1em;
            border-radius: 0.5rem;"
        >The correct answer is: '.$this->rightanswer.'</div>';
        
        $html .= "</div>";
        $html .= "</div>";

        return  $html;
    }
}
