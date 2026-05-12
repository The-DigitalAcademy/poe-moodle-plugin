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

    public string $summary = '';

    /** @var poe_assignment_submission[] */
    protected array $assignment_submissions = [];

    /** @var poe_quiz_attempt[] */
    protected array $quiz_attempts = [];

    public function __construct(int $courseid, int $cohortid = 0)
    {
        $course = get_course($courseid);

        $this->id = $course->id;
        $this->name = $course->fullname;
        $this->summary = $course->summary ?? '';

        // 🔥 FILTER HERE
        if ($cohortid) {
            $this->students = poe_student::get_students_by_cohort($this->id, $cohortid);
        } else {
            $this->students = poe_student::get_enrolled_students($this->id);
        }

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

        // =========================
        // FULL HTML WRAPPER
        // =========================

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

        .course-summary {
            font-size: 1.15rem;
            opacity: 0.9;
            max-width: 700px;
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

        .section-container {
            margin-top: 60px;
            padding-bottom: 40px;
            border-bottom: 2px solid var(--border-color);
        }

        .section-container:last-child {
            border-bottom: none;
        }

        .section-header {
            margin-bottom: 30px;
            padding-left: 20px;
            border-left: 4px solid var(--accent-color);
        }

        .section-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin: 0 0 10px 0;
            text-transform: capitalize;
        }

        .section-summary {
            font-size: 1.1rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .module-label {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--accent-color);
            margin-bottom: 8px;
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
    <h1>' . htmlspecialchars($this->name) . '</h1>
    <div class="course-summary">' . format_text($this->summary, FORMAT_HTML) . '</div>
</header>

' . $html . '

</div>

</body>
</html>';

        return $html;
    }
}
