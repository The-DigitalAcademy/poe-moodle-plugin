<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_page {
    public int $id;
    public string $name;
    public string $intro;
    public string $body;

    public function __construct(int $id, $name, $intro, $body) {
        $this->id = $id;
        $this->name = $name;
        $this->intro = $intro;
        $this->body = $body;
    }

    public function to_html(): string {
        $html = '<h2>' . $this->name . '</h2>';
        $html .= '<div class="page-intro">' . $this->intro . '</div>';
        $html .= '<div class="page-content">' . $this->body . '</div>';
        return  $html;
    }
}
