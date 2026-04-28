<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_course
{
    public int $id;
    public string $name;

    /** @var poe_student[] */
    public array $students = [];

    /** @var poe_assignment[] */
    public array $assignments = [];

    /** @var poe_quiz[] */
    public array $quizzes = [];

    public function __construct(int $courseid)
    {
        $course = get_course($courseid);

        $this->id = $course->id;
        $this->name = $course->fullname;

        // 🔥 wire everything
        $this->students = poe_student::get_enrolled_students($this->id);
        $this->assignments = $this->get_assignments();
        $this->quizzes = poe_quiz::get_course_quizzes($this->id);
    }

    /**
     * 🔥 FIXED: builds assignments with correct section + module structure
     */
    protected function get_assignments(): array
    {
        global $DB;

        $sql = "
            SELECT 
                a.id,
                a.name,
                a.intro,
                a.activity,

                cm.id AS cmid,
                m.name AS cm_module,

                cs.id AS sectionid,
                cs.name AS sectionname,
                cs.section AS sectionnumber,
                cs.summary AS sectionsummary,
                cs.sequence AS sectionsequence

            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            JOIN {assign} a ON a.id = cm.instance
            JOIN {course_sections} cs ON cs.id = cm.section

            WHERE m.name = 'assign' AND cm.course = ?
        ";

        $records = $DB->get_records_sql($sql, [$this->id]);

        $course_sections = [];
        $course_modules = [];
        $assignments = [];

        foreach ($records as $record) {

            // ✅ create section once
            if (empty($course_sections[$record->sectionid])) {
                $course_sections[$record->sectionid] = new poe_course_section(
                    $record->sectionid,
                    $record->sectionname ?? '',
                    (int)$record->sectionnumber,
                    $record->sectionsummary ?? '',
                    $record->sectionsequence ?? ''
                );
            }

            // ✅ create module once
            if (empty($course_modules[$record->cmid])) {
                $cs = $course_sections[$record->sectionid];

                $course_modules[$record->cmid] = new poe_course_module(
                    $cs,
                    $record->cmid,
                    $record->cm_module
                );
            }

            // ✅ create assignment
            $cm = $course_modules[$record->cmid];

            $assignment = new poe_assignment(
                $cm,
                $record->id,
                $record->name,
                $record->intro,
                $record->activity
            );

            $assignments[] = $assignment;
        }

        return $assignments;
    }

    /**
     * 🔥 KEEP TEAMMATE FEATURE (submission export)
     */
    public function get_assignment_submissions(): array
    {
        global $DB;

        $sql = "
            SELECT 
                s.id,
                s.assignment,
                s.userid,
                s.timecreated,
                s.timemodified,
                s.attemptnumber,

                cs.name AS sectionname,
                a.name AS assignmentname,

                u.firstname,
                u.lastname,

                at.onlinetext,
                f.id AS fileid

            FROM {assign_submission} s
            JOIN {assign} a ON a.id = s.assignment
            JOIN {course_modules} cm ON cm.instance = a.id
            JOIN {course_sections} cs ON cs.id = cm.section
            JOIN {user} u ON u.id = s.userid

            LEFT JOIN {assignsubmission_onlinetext} at ON at.submission = s.id
            LEFT JOIN {files} f ON f.itemid = s.id

            WHERE a.course = ?
              AND s.status = 'submitted'
        ";

        $records = $DB->get_records_sql($sql, [$this->id]);

        $submissions = [];

        foreach ($records as $record) {

            $submission = new poe_assignment_submission(
                $record->userid,
                "{$record->firstname} {$record->lastname}",
                $record->sectionname ?? '',
                $record->assignmentname ?? '',
                $record->attemptnumber ?? 0,
                $record->onlinetext ?? '',
                $record->fileid ?? null
            );

            $submissions[] = $submission;
        }

        return $submissions;
    }

    /**
     * 🔥 GUIDE (pages + books) — your original logic kept
     */
    public function get_html_guide(): string
    {
        global $DB;

        $html = "<h1>Guide Book</h1>";

        // PAGES
        $pages = $DB->get_records('page', ['course' => $this->id]);

        foreach ($pages as $p) {
            $page = new poe_page(
                $p->id,
                $p->name,
                $p->intro ?? '',
                $p->content ?? ''
            );

            $html .= $page->to_html();
        }

        // BOOKS
        $books_sql = "
            SELECT 
                bc.id,
                bc.bookid,
                bc.pagenum,
                bc.title,
                bc.content,
                b.name AS bookname,
                b.intro AS bookintro
            FROM {book_chapters} bc
            JOIN {book} b ON b.id = bc.bookid
            WHERE b.course = ?
        ";

        $chapters = $DB->get_records_sql($books_sql, [$this->id]);

        if (!empty($chapters)) {

            $books = [];

            foreach ($chapters as $ch) {

                if (empty($books[$ch->bookid])) {
                    $books[$ch->bookid] = new poe_book(
                        $ch->bookid,
                        $ch->bookname ?? '',
                        $ch->bookintro ?? ''
                    );
                }

                $books[$ch->bookid]->chapters[] = new poe_book_chapter(
                    $ch->id,
                    $ch->pagenum,
                    $ch->title ?? '',
                    $ch->content ?? ''
                );
            }

            foreach ($books as $book) {
                $html .= $book->to_html();
            }
        }

        return $html;
    }
}