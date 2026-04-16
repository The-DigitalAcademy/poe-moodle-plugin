<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_course_section {
    protected int $id;
    protected string $name;
    protected int $number;
    protected string $summary;
    protected array $module_sequence;
    public function __construct(int $id, string $name, int $number, string $summary, string $cm_sequence) {
        $this->id = $id;
        $this->name = $name;
        $this->number = $number;
        $this->summary = $summary;
        $this->module_sequence = array_map('intval', explode(",",$cm_sequence));
    }

    public function get_name(): string {
        return $this->name;
    }
}
