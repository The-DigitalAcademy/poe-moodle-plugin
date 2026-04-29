<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment_submission
{
    protected poe_assignment $assignment;
    protected poe_student $student;
    protected int $id;
    protected string|null $onlinetext;
    protected int|null $fileid;
    protected int|null $attempt;

    public function __construct(poe_assignment $assignment, poe_student $student, int $id, int $attempt, string|null $onlinetext = null, int|null $fileid = null) {
        $this->id = $id;
        $this->student = $student;
        $this->assignment = $assignment;
        $this->onlinetext = $onlinetext;
        $this->fileid = $fileid;
        $this->attempt = $attempt;
    }

    public function has_onlinetext(): bool {
        return is_string($this->onlinetext);
    }
    public function has_file(): bool {
        return is_int($this->fileid);
    }

    public function get_fileid(): int {
        return $this->fileid;
    }

    public function get_onlinetext(): string {
        return $this->onlinetext;
    }


    public function get_attemptnumber(): int {
        return $this->attempt;
    }
    public function get_assignment_name(): string {
        return $this->assignment->get_name();
    }

    public function get_student_fullname(): string {
        return $this->student->get_fullname();
    }

    public function get_course_section_name(): string {
        return $this->assignment->get_course_section_name();
    }


    /**
     * Summary of get_course_assignment_submissions
     * @param int $courseid
     * @return poe_assignment_submission[] all course assignment submissions
     */
    static function get_course_assignment_submissions(int $courseid): array {
        global $DB;
        $sql = "
            SELECT
                s.id AS s_id,
                cs.id AS cs_id,
                cs.name AS cs_name,
                cs.section AS cs_number,
                cs.summary AS cs_summary,
                cs.sequence AS cs_cm_sequence,
                cm.id AS cm_id,
                cm.section AS cm_section,
                m.name AS cm_module,
                a.id AS a_id,
                a.name AS a_name,
                a.intro AS a_intro,
                a.activity AS a_activity,
                a.grade AS a_maxgrade,
                u.id AS u_id,
                u.firstname AS u_firstname,
                u.lastname AS u_lastname,
                s.attemptnumber AS s_attempt,
                CASE
                    WHEN sot.id IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS s_type_onlinetext,
                sot.onlinetext AS s_onlinetext,
                CASE
                    WHEN f.id IS NOT NULL THEN TRUE
                    ELSE FALSE
                END AS s_type_file,
                f.id AS s_fileid
            FROM mdl_assign_submission s
            JOIN mdl_assign a 
                ON a.id = s.assignment
            JOIN mdl_user u 
                ON u.id = s.userid
            JOIN mdl_course_modules cm 
                ON cm.instance = a.id
            JOIN mdl_modules m 
                ON m.id = cm.module AND m.name = 'assign'
            JOIN mdl_course_sections cs 
                ON cs.id = cm.section
            LEFT JOIN mdl_assignsubmission_onlinetext sot
                ON sot.submission = s.id
            LEFT JOIN mdl_files f 
                ON f.itemid = s.id 
                AND f.component = 'assignsubmission_file'
                AND f.filearea = 'submission_files'
                AND f.filename <> '.'
            WHERE s.status = 'submitted' AND a.course = ?
        ";
        $records = $DB->get_records_sql($sql, [$courseid]);

        // associtaive arrays for id mapping
        $course_sections = [];
        $course_modules = [];
        $assignments = [];
        $students = [];
        
        $submissions = [];

        foreach ($records as $record) {
            // create course section
            if (empty($course_sections[$record->cs_id])) {
                $course_sections[$record->cs_id] = new poe_course_section($record->cs_id, $record->cs_name, $record->cs_number, $record->cs_summary, $record->cs_cm_sequence);
            }

            //  create course module
            if (empty($course_modules[$record->cm_id])) {
                $cs = $course_sections[$record->cs_id];
                $course_modules[$record->cm_id] = new poe_course_module($cs, $record->cm_id, $record->cm_module);
            }

            //  create assignment
            if (empty($assignments[$record->a_id])) {
                $cm = $course_modules[$record->cm_id];
                $assignments[$record->a_id] = new poe_assignment($cm, $record->a_id, $record->a_name, $record->a_intro, $record->a_activity, (float)$record->a_maxgrade);
            }

            // create student
            if (empty($students[$record->u_id])) {
                $students[$record->u_id] = new poe_student($record->u_id, $record->u_firstname, $record->u_lastname);
            }

            // create assignment submission
            $assignment = $assignments[$record->a_id];
            $student = $students[$record->u_id];
            $submission = new poe_assignment_submission($assignment, $student, $record->s_id, $record->s_attempt, $record->s_onlinetext, $record->s_fileid);
            array_push($submissions, $submission);
        }

        return $submissions;
    }
}