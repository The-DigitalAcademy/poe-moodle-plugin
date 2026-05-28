# Portfolio Export Plugin

The Portfolio Export Plugin is a custom **Moodle local plugin** that enables authorised users to export all learners' portfolios of evidence for a course into a structured and downloadable folder.

## Requirements
- PHP 8.1+
- Moodle 5.0+
- Composer

## Installation

1. Clone the plugin into your Moodle `local` directory:
```bash
    cd <moodle-root>/local
    git clone https://github.com/The-DigitalAcademy/poe-moodle-plugin.git poe
```

2. Install PHP dependencies (mPDF):
```bash
    cd <moodle-root>
    composer require mpdf/mpdf
```

3. Log in to Moodle as an administrator and navigate to:
    **Site Administration → Notifications**
    Moodle will detect the new plugin and prompt you to complete the installation.

## Resources

- Page API: https://docs.moodle.org/dev/Page_API
- Navigation API: https://moodledev.io/docs/5.0/apis/core/navigation