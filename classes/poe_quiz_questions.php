<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_quiz_questions {
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
        $this->mark = $mark ?? '';
        $this->answers = $answers ?? '';
    }

    public function to_html(int $index): string {
        $html = '<div class="quiz-question">';
        $html .= '<h4>Question ' . $index . '</h4>';
        $html .= '<p><strong>Type:</strong> ' . ucfirst($this->qtype) . '</p>';
        $html .= '<p><strong>Marks:</strong> ' . $this->mark . '</p>';
        $html .= '<div>' . $this->questiontext . '</div>';

        // Multiple Choice / True False / Short Answer options
        if (!empty($this->answers)) {
            $html .= '<ul>';

            foreach ($this->answers as $answer) {
                $html .= '<li>' . $answer . '</li>';
            }

            $html .= '</ul>';
        }

        // Essay questions usually do not have predefined answers
        if ($this->qtype === 'essay') {
            $html .= '<p><em>Learner must provide a written response.</em></p>';
        }

        $html .= '<hr>';
        $html .= '</div>';

        return $html;
    }
}