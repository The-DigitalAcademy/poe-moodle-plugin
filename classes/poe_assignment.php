<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment {
    public int $id;
    public string $section;
    public string $name;
    public string $intro;
    public string $activity;
    public $rubric;

    public function __construct(string $section, int $id, $name, $intro, $activity) {
        $this->section = $section;
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
}
