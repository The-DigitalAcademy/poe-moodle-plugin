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
        $this->chapters = [];
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
    public string $content;

    public function __construct(int $id, $page_number, $title, $content)
    {
        $this->id = $id;
        $this->page_number = $page_number;
        $this->title = $title;
        $this->content = $content;
    }

    public function to_html(): string
    {
        $html = $this->title;
        $html .= $this->content;
        return $html;
    }
}
