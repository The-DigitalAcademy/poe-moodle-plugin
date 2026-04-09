<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment {
    public int $id;
    public string $section;
    public string $name;
    public string $intro;
    public string $body;

    public function __construct(string $section, int $id, $name, $intro, $body) {
        $this->section = $section;
        $this->id = $id;
        $this->name = $name;
        $this->intro = $intro;
        $this->body = $body;
    }

    public function to_html(): string {
        $html = '<h2>' . $this->name . '</h2>';
        $html .= $this->intro;
        $html .= $this->body;
        return  $html;
    }
}
