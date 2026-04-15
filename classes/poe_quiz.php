<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_quiz {
    public int $id;
    public string $section;
    public string $name;
    public string $intro;
    public $questions;

    public function __construct(string $section, int $id, $name, $intro) {
        $this->section = $section;
        $this->id = $id;
        $this->name = $name ?? '';;
        $this->intro = $intro ?? '';;
    }

    public function to_html(): string {
        $html = '<h2>' . $this->name . '</h2>';
        $html .= $this->intro;
        // ...add quiz questions to html doc
        return  $html;
    }
}
