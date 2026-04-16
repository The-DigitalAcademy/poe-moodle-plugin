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
     * @var poe_quiz[]
     */
    public array $quizzes;
    public string $summary;

    public function __construct(int $id)
    {
        $course = get_course($id);
        $this->id = $course->id;
        $this->name = $course->fullname;
        $this->summary = $course->summary;
        $this->students = $this->get_enrolled_students();
        $this->assignments = $this->get_assignments();
        $this->quizzes = $this->get_quizzes();

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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 50px 40px;
            border-radius: 16px;
            margin-bottom: 40px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-bottom: 4px solid var(--accent-color);
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

    // protected function get_quizzes(): string
    // {

    // }
}
