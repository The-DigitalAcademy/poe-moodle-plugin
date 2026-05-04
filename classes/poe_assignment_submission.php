<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment_submission {

    protected int $userid;
    protected string $student_fullname;
    protected string $sectionname;
    protected string $assignmentname;
    protected int $attemptnumber;
    protected string $onlinetext;
    protected ?int $fileid;

    public function __construct(
        int $userid,
        string $student_fullname,
        string $sectionname,
        string $assignmentname,
        int $attemptnumber,
        string $onlinetext = '',
        ?int $fileid = null
    ) {
        $this->userid = $userid;
        $this->student_fullname = $student_fullname;
        $this->sectionname = $sectionname;
        $this->assignmentname = $assignmentname;
        $this->attemptnumber = $attemptnumber;
        $this->onlinetext = $onlinetext ?? '';
        $this->fileid = $fileid;
    }

    /**
     * 🔥 Render submission as full HTML document
     */
    public function to_html(): string {

        $html = poe_renderer::get_styles();

        $html .= '<div class="submission-block">';
        $html .= '<h2>Assignment Submission</h2>';

        // 🔥 METADATA
        $html .= '<div class="metadata">';
        $html .= '<h3>Submission Details</h3>';

        $html .= '<p><strong>Student:</strong> ' . format_string($this->student_fullname) . '</p>';
        $html .= '<p><strong>Module:</strong> ' . format_string($this->sectionname) . '</p>';
        $html .= '<p><strong>Assignment:</strong> ' . format_string($this->assignmentname) . '</p>';
        $html .= '<p><strong>Attempt:</strong> ' . $this->attemptnumber . '</p>';
        $html .= '<p><strong>Status:</strong> Submitted</p>';
        $html .= '<p><strong>Venue:</strong> LMS (Online)</p>';

        $html .= '</div>';

        // 🔥 CONTENT
        $html .= '<div class="submission-content">';
        $html .= '<h3>Submission Content</h3>';

        $cleanContent = format_text($this->onlinetext ?? '', FORMAT_HTML);

        $html .= $cleanContent ?: '<em>No content submitted</em>';

        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * 🔹 Getters (used in export.php)
     */
    public function get_student_fullname(): string {
        return $this->student_fullname;
    }

    public function get_course_section_name(): string {
        return $this->sectionname;
    }

    public function get_assignment_name(): string {
        return $this->assignmentname;
    }

    public function get_attemptnumber(): int {
        return $this->attemptnumber;
    }

    public function get_onlinetext(): string {
        return $this->onlinetext;
    }

    public function has_onlinetext(): bool {
        return !empty(trim($this->onlinetext));
    }

    public function has_file(): bool {
        return !empty($this->fileid);
    }

    public function get_fileid(): ?int {
        return $this->fileid;
    }
}