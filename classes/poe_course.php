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

    public function __construct(int $id)
    {
        $course = get_course($id);
        $this->id = $course->id;
        $this->name = $course->fullname;
        $this->summary = $course->summary;
        $this->students = poe_student::get_enrolled_students($this->id);
        $this->assignments = poe_assignment::get_course_assignments($this->id);
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

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($this->name) . ' - Learner Guide</title>
    <style>
        :root {
            --primary-color: #0f172a;
            --accent-color: #3b82f6;
            --secondary-color: #64748b;
            --bg-color: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --code-bg: #1e293b;
            --code-text: #f8fafc;
        }
        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-main);
            background-color: var(--bg-color);
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .course-header {
            background: transparent;
            color: var(--text-main);
            padding: 40px 0;
            margin-bottom: 40px;
            border-bottom: 2px solid var(--border-color);
        }
        .course-header h1 {
            margin: 0 0 16px 0;
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        pre, code {
            font-family: "JetBrains Mono", "Fira Code", "Courier New", monospace;
            background-color: var(--code-bg);
            color: var(--code-text);
            border-radius: 8px;
        }
        pre {
            padding: 20px;
            overflow-x: auto;
            margin: 20px 0;
            line-height: 1.45;
            font-size: 0.95rem;
        }
        code {
            padding: 2px 6px;
            font-size: 0.9em;
        }
        .course-summary {
            font-size: 1.15rem;
            opacity: 0.9;
            max-width: 700px;
        }
        .section-type-header {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 8px;
            margin: 40px 0 24px 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .content-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }
        .content-card h2 {
            margin-top: 0;
            color: #0f172a;
            font-size: 1.75rem;
        }
        .book-container {
            border-left: 4px solid var(--primary-color);
            padding-left: 24px;
        }
        .chapter-container {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px dashed var(--border-color);
        }
        .chapter-title {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="course-header">
            <h1>' . $this->name . '</h1>
            <div class="course-summary">' . $this->summary . '</div>
        </header>';

        // PAGES
        $page_records = $DB->get_records('page', ['course' => $this->id], '', 'id,name,intro,content');
        if (!empty($page_records)) {
            $html .= '<div class="section-type-header">Content Pages</div>';
            $pages = array_map(function ($item) {
                return new poe_page($item->id, $item->name, $item->intro, $item->content);
            }, $page_records);

            foreach ($pages as $page) {
                $html .= '<div class="content-card">' . $page->to_html() . '</div>';
            }
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
            $html .= '<div class="section-type-header">Books</div>';
            /**
             * @var array<int, poe_book> associative array. stores books by id
             */
            $books = [];

            foreach ($book_chapter_records as $book_chapter) {
                // create a book
                if (empty($books[$book_chapter->bookid])) {
                    $books[$book_chapter->bookid] = new poe_book($book_chapter->bookid, $book_chapter->bookname, $book_chapter->bookintro);
                }
                //  add each chapter to a book
                array_push($books[$book_chapter->bookid]->chapters, new poe_book_chapter($book_chapter->id, $book_chapter->pagenum, $book_chapter->title, $book_chapter->content));
            }

            foreach ($books as $book) {
                $html .= '<div class="content-card book-container">' . $book->to_html() . '</div>';
            }
        }

        $html .= '</div></body></html>';

        return $html;
    }
}