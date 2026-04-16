<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment {
    public int $id;
    public string $section;
    public string $name;
    public string $intro;
    public string $activity;
    public array $rubric = [];

    public function __construct(string $section, int $id, $name, $intro, $activity) {
        $this->section = $section;
        $this->id = $id;
        $this->name = $name;
        $this->intro = $intro;
        $this->activity = $activity;
    }

    public function to_html(): string {
        $html = '<h2>' . $this->name . '</h2>';
        $html .= $this->intro;
        $html .= $this->activity;

        // rubric
        if (!empty($this->rubric)) {
            // group levels by criteria
            $criteria = [];
            foreach ($this->rubric as $row) {
                $criteria[$row['criteria']][] = [
                    'definition' => $row['definition'],
                    'score'      => (int) $row['score'],
                ];
            }

            $html .= '<h3>Rubric</h3>';
            $html .= '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%;">';

            $i = 0;
            foreach ($criteria as $criteria_name => $levels) {
                $bg = ($i % 2 === 0) ? '#f2f2f2' : '#ffffff';
                $html .= '<tr style="background-color:' . $bg . ';">';        
                // criteria name as first cell
                $html .= '<td><strong>' . $criteria_name . '</strong></td>';
                // each level as its own cell
                foreach ($levels as $level) {
                    $html .= '<td>';
                    $html .= $level['definition'] . '<br>';
                    $html .= '<text style="color: green;">' . $level['score'] . ' points' . '</text>';
                    $html .= '</td>';
                }
                $html .= '</tr>';
                $i++;
            }

            $html .= '</table>';
        }

        return  $html;
    }
}
