<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_course_module {
    protected int $id;
    protected string $module;
    protected poe_course_section $course_section;
    public function __construct(poe_course_section $course_section, int $id, string $module) {
        $this->id = $id;
        $this->module = $module;
        $this->course_section = $course_section;
    }

    public function get_course_section_name():string {
        return $this->course_section->get_name();
    }
}
