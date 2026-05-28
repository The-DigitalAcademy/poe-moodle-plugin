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

    /**
     * 🔥 REQUIRED (used everywhere)
     */
    public function get_fullname(): string {
        return trim("{$this->firstname} {$this->lastname}");
    }

    /**
     * OPTIONAL (nice for debugging / extensions)
     */
    public function get_firstname(): string {
        return $this->firstname;
    }

    public function get_lastname(): string {
        return $this->lastname;
    }

    /**
     * 🔥 STATIC LOADER
     */
    public static function get_enrolled_students(int $courseid): array {
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
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
            JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = 50 AND ctx.instanceid = e.courseid
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