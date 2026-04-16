<?php
namespace local_poe;

use Exception;

defined('MOODLE_INTERNAL') || die();

class poe_course
{
    public int $id;
    public string $name;
    /**
     * @var poe_student[]
     */
    public array $students;
    /**
     * @var poe_assignment[]
     */
    public array $assignments;
    /**
     * Summary of quizzes
     * @var poe_quiz[]
     */
    public array $quizzes;

    public function __construct(int $id)
    {
        $course = get_course($id);
        $this->id = $course->id;
        $this->name = $course->fullname;
        $this->students = $this->get_enrolled_students();
        $this->assignments = $this->get_assignments();
        $this->quizzes = $this->get_quizzes();

    }

    public function get_html_guide(): string
    {
        global $DB;

        $html = "<h1> Guide Book</h1>";

        // PAGES
        $page_records = $DB->get_records('page', ['course' => $this->id], '', 'id,name,intro,content');
        $pages = array_map(function ($item) {
            $page = new poe_page($item->id, $item->name, $item->intro, $item->content);
            return $page;
        }, $page_records);

        foreach ($pages as $page) {
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
            JOIN {book} b
                ON b.id = bc.bookid
            WHERE b.course = ?
        ";

        $book_chapter_records = $DB->get_records_sql($books_sql, [$this->id]);
        if (!empty($book_chapter_records)) {
            /**
             * @var array<int, poe_book> associative array. stores books by id
             */
            $books = [];

            $html .= '<h3>BOOKS ARRAY:</h3> ';
            $html .= count($books);

            foreach ($book_chapter_records as $book_chapter) {
                // create a book
                if (empty($books[$book_chapter->bookid])) {
                    $books[$book_chapter->bookid] = new poe_book($book_chapter->bookid, $book_chapter->bookname, $book_chapter->bookintro);
                }
                //  add each chapter to a book
                array_push($books[$book_chapter->bookid]->chapters, new poe_book_chapter($book_chapter->id, $book_chapter->pagenum, $book_chapter->title, $book_chapter->content));
            }

            foreach ($books as $book) {
                $html .= $book->to_html();
            }
        }

        return $html;
    }

    /**
     * @return poe_assignment[] assignments
     */
    protected function get_assignments(): array
    {
        global $DB;
        $sql = "
            SELECT 
                grl.id AS id,
                a.id AS assignment_id,
                a.name,
                a.intro,
                a.activity,
                cs.name AS section,
                grc.id AS criteria_id,
                grc.description AS criteria_description,
                grc.sortorder AS criteria_sortorder,
                grl.id AS level_id,
                grl.score,
                grl.definition AS level_definition
            FROM {course_modules} cm
            JOIN {modules} m 
                ON m.id = cm.module
            JOIN {assign} a
                ON a.id = cm.instance
            JOIN {course_sections} cs 
                ON cs.id = cm.section
            LEFT JOIN {context} ctx
                ON ctx.instanceid = cm.id AND ctx.contextlevel = 70
            LEFT JOIN {grading_areas} ga
                ON ga.contextid = ctx.id AND ga.component = 'mod_assign'
            LEFT JOIN {grading_definitions} gd
                ON gd.areaid = ga.id AND gd.method = 'rubric'
            LEFT JOIN {gradingform_rubric_criteria} grc
                ON grc.definitionid = gd.id
            LEFT JOIN {gradingform_rubric_levels} grl
                ON grl.criterionid = grc.id
            WHERE m.name = 'assign' AND cm.course = ?
            ORDER BY a.id, grc.sortorder, grl.score
        ";

        $records = $DB->get_records_sql($sql, [$this->id]);

        $assignments = [];
        foreach ($records as $item) {
            // create assignment if we haven't seen it yet
            if (empty($assignments[$item->assignment_id])) {
                $assignments[$item->assignment_id] = new poe_assignment(
                    $item->section,
                    $item->assignment_id,
                    $item->name,
                    $item->intro,
                    $item->activity
                );
            }
            // append rubric criterion/level if it exists
            if (!empty($item->criteria_id)) {
                $assignments[$item->assignment_id]->rubric[] = [
                    'criteria'   => $item->criteria_description,
                    'score'      => $item->score,
                    'definition' => $item->level_definition,
                ];
            }
        }

        return array_values($assignments);
    }

    /**
     * @return poe_quiz[] assignments
     */
    protected function get_quizzes(): array
    {
        global $DB;
        $sql = "
            SELECT 
                q.id,
                q.name,
                q.intro,
                cs.name AS section
            FROM {course_modules} cm
            JOIN {modules} m 
                ON m.id = cm.module
            JOIN {quiz} q
                ON q.id = cm.instance
            JOIN {course_sections} cs 
                ON cs.id = cm.section
            WHERE m.name = 'quiz' AND cm.course = ?
        ";

        $records = $DB->get_records_sql($sql, [$this->id]);

        $quizzes = array_map(function ($item) {
            return new poe_quiz($item->section, $item->id, $item->name, $item->intro);
        }, $records);

        return $quizzes;
    }

    protected function get_enrolled_students()
    {
        global $DB;

        $sql = "
            SELECT 
                u.id,
                u.firstname,
                u.lastname
            FROM {user_enrolments} ue
            JOIN {enrol} e
                ON e.id = ue.enrolid
            JOIN {user} u 
                ON u.id = ue.userid
            WHERE e.courseid = ?
        ";

        $records = $DB->get_records_sql($sql, [$this->id]);

        $students = array_map(function ($item) {
            return new poe_student($item->id, "{$item->firstname} {$item->lastname}");
        }, $records);
        return $students;
    }

    // protected function get_quizzes(): string
    // {

    // }
}
