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
     * @var poe_quiz[]
     */
    public array $quizzes;
    public string $summary;

    /**
     * @var poe_assignment_submission[]
     */
    protected array $assignment_submissions;
    /**
     * @var poe_quiz_attempt[]
     */
    protected array $quiz_attempts;

    public function __construct(int $courseid)
    {
        $course = get_course($courseid);

        $this->id = $course->id;
        $this->name = $course->fullname;

        // 🔥 wire everything
        $this->students = poe_student::get_enrolled_students($this->id);
        $this->assignments = $this->get_assignments();
        $this->quizzes = poe_quiz::get_course_quizzes($this->id);
        $this->assignment_submissions = poe_assignment_submission::get_course_assignment_submissions($this->id);
        $this->quiz_attempts = poe_quiz_attempt::get_all_quiz_attempts($this->id);
    }


    /**
     * @return poe_assignment_submission[]
     */
    public function get_assignment_submissions(): array {
        return $this->assignment_submissions;
    }

    /**
     * @return poe_quiz_attempt[]
     */
    public function get_quiz_attempts(): array {
        return $this->quiz_attempts;
    }

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