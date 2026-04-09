<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_course
{
    public int $id;
    public string $name;
    public string $guide;
    /**
     * @var poe_assignment[]
     */
    public string $assignments;
    /**
     * Summary of quizzes
     * @var poe_quiz[]
     */
    public string $quizzes;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->assignments = $this->get_assignments();
    }

    /**
     * @return poe_assignment[] assignments
     */
    protected function get_assignments(): array
    {
        global $DB;
        $sql = "
            SELECT id, name, intro, activity
            FROM {assign}
            WHERE course = ?
        ";

        $records = $DB->get_records_sql($sql, [$this->id]);

        $assingments = array_map(function($item) {
            return new poe_assignment('sect', $item->id, $item->name, $item->intro, $item->activity);
        }, $records);
        
        return $assingments;
    }

    // protected function get_quizzes(): string
    // {

    // }
}
