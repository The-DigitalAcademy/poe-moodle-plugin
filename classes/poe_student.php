<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_student {
    public int $id;
    public string $name;
    /**
     * @var poe_assignment_submission[]
     */
    public $assingment_submissions;
    public $quiz_submissions;

    public function __construct(int $id, $name) {
        $this->id = $id;
        $this->name = $name;
    }
}
