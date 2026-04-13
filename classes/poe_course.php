<?php
namespace local_poe;

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

        $this->set_student_assignment_submissions();

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
                a.id,
                a.name,
                a.intro,
                a.activity,
                cs.name AS section
            FROM {course_modules} cm
            JOIN {modules} m 
                ON m.id = cm.module
            JOIN {assign} a
                ON a.id = cm.instance
            JOIN {course_sections} cs 
                ON cs.id = cm.section
            WHERE m.name = 'assign' AND cm.course = ?
        ";

        $records = $DB->get_records_sql($sql, [$this->id]);

        $assingments = array_map(function ($item) {
            return new poe_assignment($item->section, $item->id, $item->name, $item->intro, $item->activity);
        }, $records);

        return $assingments;
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

    /**
     * Set each student's assignment submissions for each assignment in the course
     * @return void
     */
    public function set_student_assignment_submissions() {
        global $DB;
        $sql = "
            SELECT
                s.id,
                s.userid,
                s.assignment,
                s.status,
                sot.onlinetext,
                f.id AS fileid,
                CASE 
                    WHEN sot.id IS NOT NULL THEN 'onlinetext'
                    WHEN f.id IS NOT NULL THEN 'file'
                    ELSE NULL
                END AS type
            FROM {assign_submission} s
            JOIN {assign} a
                ON a.id = s.assignment 
            LEFT JOIN {assignsubmission_onlinetext} sot
                on sot.submission = s.id 
            LEFT JOIN {files} f
                ON f.itemid = s.id
                AND f.component = 'assignsubmission_file'
                AND f.filearea = 'submission_files'
                AND f.filename <> '.'   
            WHERE s.status = 'submitted' AND a.course = ?
        ";
        $records = $DB->get_records_sql($sql, [$this->id]);

        // group assignment submissions by userid for easy allocation to each student
        $grouped = array_reduce($records, function ($carry, $item) {
            // set content by submission type type
            $content = null;
            if ($item->type == 'onlinetext') $content = $item->onlinetext;
            elseif ($item->type == 'file') $content = $item->fileid; 

            // add submisssion to array as per userid key
            $carry[$item->userid][] = new poe_assignment_submission($item->id, $item->type, $item->assignment, $content);
            return $carry;
        }, []);

        // assign submissions to students
        foreach ($this->students as $student) {
            $student->assingment_submissions = $grouped[$student->id] ?? [];
        }
    }
}
