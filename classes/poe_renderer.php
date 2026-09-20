<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_renderer {

    public static function get_styles(): string {
        return '
        <style>
            body { font-family: Arial, sans-serif; }
            h2 { border-bottom: 2px solid #333; }
            .metadata {
                background: #f5f5f5;
                padding: 10px;
                margin-bottom: 15px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            td {
                border: 1px solid #ddd;
                padding: 6px;
            }
                .quiz-question {
            margin-bottom: 20px;
        }

        .question-text {
            margin: 10px 0;
        }

        .answers {
            list-style: none;
            padding-left: 0;
        }

        .answers li {
            padding: 5px 0;
        </style>
        ';
    }
}