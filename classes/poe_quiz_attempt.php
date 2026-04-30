<?php
namespace local_poe;

use DateTime;

defined('MOODLE_INTERNAL') || die();

class poe_quiz_attempt {
    /**
     * @var poe_quiz_question_attempt[]
     */
    protected array $question_attempts;
    protected int $attempt;
    protected string $status;
    protected int $timestart;
    protected int $timefinish;
    protected float|null $sumgrades;
    protected string $feedback;
    protected string $username;
    protected string $quizname;
    protected string $sectionname;

    public function __construct(string $status, int $timestart, int $timefinish, int $attempt, float|null $sumgrades) {
        $this->attempt = $attempt;
        $this->status = $status;
        $this->timestart = $timestart;
        $this->timefinish = $timefinish;
        $this->sumgrades = $sumgrades;
        $this->question_attempts = [];
    }

    public function to_html(): string {
        $html = '<div style="padding:0 3rem;">';
        $html .= "<h1>{$this->quizname}</h1>";

        $startdate = new DateTime("@$this->timestart");
        $finishdate = new DateTime("@$this->timefinish");
        $duration_sec = abs($this->timestart - $this->timefinish);
        $duration_str = floor($duration_sec / 60) . 'mins ' . $duration_sec % 60 . 'secs' ;

        // metadata table
        $html .= '<div style="margin-bottom: 1rem">';
        $html .= '<table><tbody>';
        $html .= '<tr><th>Name</th><td>'. $this->username .'</td></tr>';
        $html .= '<tr><th>Status</th><td>'. $this->status .'</td></tr>';
        $html .= '<tr><th>Started</th><td>'. $startdate->format('l, d F Y, g:i A') .'</td></tr>';
        $html .= '<tr><th>Completed</th><td>'. $finishdate->format('l, d F Y, g:i A') .'</td></tr>';
        $html .= '<tr><th>Duration</th><td>'. $duration_str .'</td></tr>';
        $html .= '<tr><th>Grade</th><td>'. $this->sumgrades .' out of ' . $this->get_maxmark() . '</td></tr>';
        $html .= '<tr><th>Feedback</th><td>'. $this->feedback .'</td></tr>';
        $html .= '</tbody></table>';
        $html .= '</div>';
        $html .= '<hr>';

        $html .= '<section>';
        foreach ($this->question_attempts as $qa) {
            $html .= $qa->to_html();
        }
        $html .= "</section>";
        $html .= "<div>";
        return  $html;
    }

    public function get_username():string {
        return $this->username;
    }
    public function get_sectionname(): string {
        return $this->sectionname;
    }

    public function get_quizname():string {
        return $this->quizname;
    }

    public function get_attemptnumber():int {
        return $this->attempt;
    }

    public function get_maxmark(): float {
        return array_reduce($this->question_attempts, fn($carry, $item) => $carry + $item->get_maxmark(), 0);
    }

    /**
     * @param poe_quiz_question_attempt[] $attempts
     * @return void
     */
    protected function set_question_attempts(array $attempts) {
        $this->question_attempts = $attempts;
    }

    public function set_student(string $name) {
        $this->username = $name;
    }
    public function set_sectionname(string $name) {
        $this->sectionname = $name;
    }
    public function set_quizname(string $name) {
        $this->quizname = $name;
    }
    public function set_feedback(string $text) {
        $this->feedback = $text;
    }


    static function get_all_quiz_attempts(int $courseid) {
        global $DB;
        $qa_sql = "
            SELECT qa.id, qa.state, qa.timestart, qa.timefinish, qa.sumgrades, qa.attempt, qa.uniqueid,
                    u.firstname, u.lastname,
                    q.name AS quizname,
                    cs.name AS sectionname
            FROM {quiz_attempts} qa 
            JOIN {user} u ON u.id = qa.userid
            JOIN {quiz} q ON q.id = qa.quiz
            JOIN {course_modules} cm ON cm.instance = q.id
            JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
            JOIN {course_sections} cs ON cs.id = cm.section
            WHERE q.course = ?";

        $qa_records = $DB->get_records_sql($qa_sql, [$courseid]);
        $quiz_feedback = $DB->get_records('quiz_feedback');

        $quiz_attempts = [];
        $question_attempts = poe_quiz_question_attempt::get_question_attempts($courseid);

        foreach ($qa_records as $value) {
            $qa = new poe_quiz_attempt($value->state, $value->timestart, $value->timefinish, $value->attempt, $value->sumgrades);
            $qa->set_student($value->firstname . " " . $value->lastname);
            $qa->set_sectionname($value->sectionname);
            $qa->set_quizname($value->quizname);
            $qa->set_question_attempts(array_filter($question_attempts, fn($val) => $val->get_usageid() == $value->uniqueid));

            $filtered_feedback = array_filter($quiz_feedback, fn($fb) => $value->sumgrades >= $fb->mingrade && $value->sumgrades <= $fb->maxgrade);
            $qa->set_feedback(reset($filtered_feedback)->feedbacktext ?? '');

            array_push($quiz_attempts, $qa);
        }

        return $quiz_attempts;
    }
}
