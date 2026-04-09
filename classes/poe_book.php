<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_book
{
    public int $id;
    public string $name;
    public string $intro;
    /**
     * Summary of pages
     * @var poe_book_chapter[] book chapters
     */
    public array $chapters;

    public function __construct(int $id, $name, $intro)
    {
        $this->id = $id;
        $this->name = $name;
        $this->intro = $intro;
    }

    public function to_html(): string
    {
        $html = '<h2>' . $this->name . '</h2>';
        $html .= $this->intro;

        foreach ($this->chapters as $chapter) {
            $html .= $chapter->to_html();
        }
        
        return $html;
    }
}

class poe_book_chapter
{
    public int $id;
    public int $page_number;
    public string $title;
    public string $body;

    public function __construct(int $id, $page_number, $title, $body)
    {
        $this->id = $id;
        $this->page_number = $page_number;
        $this->title = $title;
        $this->body = $body;
    }

    public function to_html(): string
    {
        $html = $this->title;
        $html .= $this->body;
        return $html;
    }
}
