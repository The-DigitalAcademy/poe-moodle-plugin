<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_student {
    protected int $id;
    protected string $firstname;
    protected string $lastname;
    protected array $quiz_submissions = [];

    public function __construct(int $id, string $firstname, string $lastname) {
        $this->id = $id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }

    public function get_id(): int {
        return $this->id;
    }

  

    static function get_enrolled_students(int $courseid): array {
        global $DB;

        $students = [];

        $sql = "
            SELECT 
                u.id,
                u.firstname,
                u.lastname
            FROM {user_enrolments} ue
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {user} u ON u.id = ue.userid
            WHERE e.courseid = ?
        ";

        $records = $DB->get_records_sql($sql, [$courseid]);

        foreach ($records as $record) {
            $students[] = new poe_student(
                $record->id,
                $record->firstname,
                $record->lastname
            );
        }

        return $students;
    }
}