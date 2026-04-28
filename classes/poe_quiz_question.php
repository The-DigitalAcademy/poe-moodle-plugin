<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_quiz_question {
    public int $id;
    public string $name;
    public string $questiontext;
    public string $qtype;
    public float $mark;
    public array $answers;

    public function __construct(
        int $id,
        string $name,
        string $questiontext,
        string $qtype,
        float $mark = 0,
        array $answers = []
    ) {
        $this->id = $id;
        $this->name = $name ?? '';
        $this->questiontext = $questiontext ?? '';
        $this->qtype = $qtype ?? '';
       $this->mark = $mark ?? 0;
$this->answers = $answers ?? [];
    }
public function to_html(int $index): string {

    // 🔥 Clean question text (remove numbering + HTML tags)
    $cleanText = preg_replace('/^\d+\.\s*/', '', strip_tags($this->questiontext));

    $html = '<div class="quiz-question">';
    $html .= '<h4>Question ' . $index . '</h4>';

    // Optional: hide type if you don't want it visible
    $html .= '<p><strong>Marks:</strong> ' . $this->mark . '</p>';

    $html .= '<div class="question-text"><strong>' . $cleanText . '</strong></div>';

    // 🔥 Answer formatting
    if (!empty($this->answers)) {

        $html .= '<ul class="answers">';

        $labels = ['A', 'B', 'C', 'D', 'E'];

        foreach ($this->answers as $i => $answer) {

            $cleanAnswer = strip_tags($answer);

            $html .= '<li><strong>' . ($labels[$i] ?? '') . '.</strong> ' . $cleanAnswer . '</li>';
        }

        $html .= '</ul>';
    }

    // Essay handling
    if ($this->qtype === 'essay') {
        $html .= '<p><em>Learner must provide a written response.</em></p>';
    }

    $html .= '</div><hr>';

    return $html;
}
}