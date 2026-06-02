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

    /**
     * Returns an ordered map of section ID => numeric prefix (e.g., "01", "02")
     * based on the visual sequence in Moodle (handling subsections).
     */
    public static function get_section_prefixes(int $courseid): array {
        global $DB;
        
        $all_sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');
        $ordered_sections = [];

        foreach ($all_sections as $sec) {
            if (!isset($sec->component) || empty($sec->component)) {
                $ordered_sections[] = $sec;
                
                if (!empty($sec->sequence)) {
                    $cm_ids = explode(',', $sec->sequence);
                    foreach ($cm_ids as $cm_id) {
                        $cm_id = trim($cm_id);
                        if ($cm_id === '') continue;
                        
                        $mod_info = $DB->get_record_sql("
                            SELECT cm.id, m.name as modname, cm.instance
                            FROM {course_modules} cm
                            JOIN {modules} m ON m.id = cm.module
                            WHERE cm.id = ? AND m.name = 'subsection'
                        ", [(int)$cm_id]);
                        
                        if ($mod_info) {
                            foreach ($all_sections as $sub_sec) {
                                if (isset($sub_sec->component) && $sub_sec->component === 'mod_subsection' && $sub_sec->itemid == $mod_info->instance) {
                                    $ordered_sections[] = $sub_sec;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $prefixes = [];
        $counter = 1;
        foreach ($ordered_sections as $section) {
            // Only prefix sections that actually have content or a name
            // (Similar to the skip logic in get_html_guide)
            $prefixes[$section->id] = str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $prefixes;
    }

   public function __construct(int $id, int $groupid = 0)
    {
        $course = get_course($id);

        $this->id = $course->id;
        $this->name = $course->fullname;
        $this->summary = $course->summary ?? '';

  if ($groupid > 0) {
    $this->students = poe_student::get_students_by_group($this->id, $groupid);
} else {
   $this->students = poe_student::get_enrolled_students($this->id);
}
        //  aligned with develop
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
                .container { max-width: 900px; margin: 0 auto; }
                .course-header {
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
                pre { padding: 20px; overflow-x: auto; margin: 20px 0; font-size: 0.95rem; }
                code { padding: 2px 6px; font-size: 0.9em; }
                .course-summary { font-size: 1.15rem; opacity: 0.9; max-width: 700px; }
                .content-card {
                    background: var(--card-bg);
                    border: 1px solid var(--border-color);
                    border-radius: 12px;
                    padding: 32px;
                    margin-bottom: 24px;
                    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
                }
                .content-card h2 { margin-top: 0; color: #0f172a; font-size: 1.75rem; }
                .book-container { border-left: 4px solid var(--primary-color); padding-left: 24px; }
                .chapter-container { margin-top: 32px; padding-top: 24px; border-top: 1px dashed var(--border-color); }
                .chapter-title { color: var(--primary-color); font-size: 1.25rem; font-weight: 600; margin-bottom: 16px; }
                .section-container { margin-top: 60px; padding-bottom: 40px; border-bottom: 2px solid var(--border-color); }
                .section-container:last-child { border-bottom: none; }
                .section-header { margin-bottom: 30px; padding-left: 20px; border-left: 4px solid var(--accent-color); }
                .section-title { font-size: 2rem; font-weight: 800; color: var(--primary-color); margin: 0 0 10px 0; }
                .section-summary { font-size: 1.1rem; color: var(--text-muted); line-height: 1.6; }
                .module-label {
                    display: inline-block;
                    font-size: 0.75rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: var(--accent-color);
                    margin-bottom: 8px;
                }
                img { max-width: 100%; height: auto; border-radius: 8px; }
            </style>
        </head>
        <body>
        <div class="container">
        <header class="course-header">
            <h1>' . htmlspecialchars($this->name) . '</h1>
            <div class="course-summary">' . $this->summary . '</div>
        </header>';

        // ── Build section order (handles Moodle 4.3+ subsections) ────────────────
        $all_sections = $DB->get_records('course_sections', ['course' => $this->id], 'section ASC');
        $sections     = [];

        foreach ($all_sections as $sec) {
            if (!isset($sec->component) || empty($sec->component)) {
                $sections[] = $sec;

                if (!empty($sec->sequence)) {
                    foreach (explode(',', $sec->sequence) as $cm_id) {
                        $cm_id = trim($cm_id);
                        if ($cm_id === '') continue;

                        $mod_info = $DB->get_record_sql("
                            SELECT cm.id, m.name AS modname, cm.instance
                            FROM {course_modules} cm
                            JOIN {modules} m ON m.id = cm.module
                            WHERE cm.id = ? AND m.name = 'subsection'
                        ", [(int) $cm_id]);

                        if ($mod_info) {
                            foreach ($all_sections as $sub_sec) {
                                if (isset($sub_sec->component)
                                    && $sub_sec->component === 'mod_subsection'
                                    && $sub_sec->itemid == $mod_info->instance) {
                                    $sections[] = $sub_sec;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        // ── Render sections ───────────────────────────────────────────────────────
        foreach ($sections as $section) {
            $section_modules = [];

            if (!empty($section->sequence)) {
                foreach (explode(',', $section->sequence) as $cm_id) {
                    $cm_id = trim($cm_id);
                    if ($cm_id === '') continue;

                    $mod_info = $DB->get_record_sql("
                        SELECT cm.id, m.name AS modname, cm.instance
                        FROM {course_modules} cm
                        JOIN {modules} m ON m.id = cm.module
                        WHERE cm.id = ? AND m.name IN ('page', 'book')
                    ", [(int) $cm_id]);

                    if ($mod_info) {
                        $section_modules[] = $mod_info;
                    }
                }
            }

            // Skip empty sections
            if (empty($section_modules)
                && empty(trim(strip_tags($section->summary ?? '')))
                && empty($section->name)) {
                continue;
            }

            $section_name = !empty($section->name)
                ? $section->name
                : get_string('sectionname', 'format_topics') . ' ' . $section->section;

            if ($section->section == 0 && empty($section->name)) {
                $section_name = get_string('general');
            }

            $html .= '<section class="section-container">';
            $html .= '<div class="section-header">';
            $html .= '<h2 class="section-title">' . htmlspecialchars($section_name) . '</h2>';

            if (!empty($section->summary)) {
                $context           = \context_course::instance($this->id);
                $formatted_summary = format_text($section->summary, $section->summaryformat, ['context' => $context]);
                $html             .= '<div class="section-summary">' . $formatted_summary . '</div>';
            }

            $html .= '</div>'; // .section-header

            foreach ($section_modules as $mod) {
                if ($mod->modname === 'page') {
                    $page_record = $DB->get_record('page', ['id' => $mod->instance]);
                    if ($page_record) {
                        $page  = new poe_page($page_record->id, $page_record->name, $page_record->intro, $page_record->content);
                        $html .= '<div class="content-card">';
                        $html .= '<span class="module-label">Page</span>';
                        $html .= $page->to_html();
                        $html .= '</div>';
                    }

                } elseif ($mod->modname === 'book') {
                    $book_record = $DB->get_record('book', ['id' => $mod->instance]);
                    if ($book_record) {
                        $book     = new poe_book($book_record->id, $book_record->name, $book_record->intro);
                        $chapters = $DB->get_records('book_chapters', ['bookid' => $book_record->id], 'pagenum ASC');

                        foreach ($chapters as $chapter) {
                            $book->chapters[] = new poe_book_chapter(
                                $chapter->id,
                                $chapter->pagenum,
                                $chapter->title,
                                $chapter->content
                            );
                        }

                        $html .= '<div class="content-card book-container">';
                        $html .= '<span class="module-label">Book</span>';
                        $html .= $book->to_html();
                        $html .= '</div>';
                    }
                }
            }

            $html .= '</section>'; // .section-container
        }

        $html .= '</div></body></html>';
            
        return $html;
    }    

    public function get_pdf_guide(): string
    {
        global $CFG;
        require_once(dirname($CFG->dirroot) . '/vendor/autoload.php');

        $html = $this->get_html_guide();

        // mPDF doesn't support CSS custom properties — replace with literal values
        $html = str_replace(
            [
                'var(--primary-color)',
                'var(--accent-color)',
                'var(--secondary-color)',
                'var(--bg-color)',
                'var(--card-bg)',
                'var(--text-main)',
                'var(--text-muted)',
                'var(--border-color)',
                'var(--code-bg)',
                'var(--code-text)',
            ],
            [
                '#0f172a',
                '#3b82f6',
                '#64748b',
                '#f1f5f9',
                '#ffffff',
                '#1e293b',
                '#64748b',
                '#e2e8f0',
                '#1e293b',
                '#f8fafc',
            ],
            $html
        );

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
            'tempDir'       => sys_get_temp_dir(),
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

   /*  public function get_pdf_guides(): string
    {
        global $CFG;
        require_once(dirname($CFG->dirroot) . '/vendor/autoload.php');

        $html = $this->get_html_guide();

        // mPDF doesn't support CSS custom properties — replace with literal values
        $html = str_replace(
            [
                'var(--primary-color)',
                'var(--accent-color)',
                'var(--secondary-color)',
                'var(--bg-color)',
                'var(--card-bg)',
                'var(--text-main)',
                'var(--text-muted)',
                'var(--border-color)',
                'var(--code-bg)',
                'var(--code-text)',
            ],
            [
                '#0f172a',
                '#3b82f6',
                '#64748b',
                '#f1f5f9',
                '#ffffff',
                '#1e293b',
                '#64748b',
                '#e2e8f0',
                '#1e293b',
                '#f8fafc',
            ],
            $html
        );

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_left'   => 15,
            'margin_right'  => 15,
            'tempDir'       => sys_get_temp_dir(),
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    } */
}
