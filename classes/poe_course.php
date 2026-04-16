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

    /**
     * @var poe_assignment_submission[]
     */
    protected array $assignment_submissions;

    public function __construct(int $id)
    {
        $course = get_course($id);
        $this->id = $course->id;
        $this->name = $course->fullname;
        $this->students = poe_student::get_enrolled_students($this->id);
        $this->assignments = poe_assignment::get_course_assignments($this->id);
        $this->quizzes = poe_quiz::get_course_quizzes($this->id);
        $this->assignment_submissions = poe_assignment_submission::get_course_assignment_submissions($this->id);
    }

    /**
     * @return poe_assignment_submission[]
     */
    public function get_assignment_submissions(): array {
        return $this->assignment_submissions;
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
}