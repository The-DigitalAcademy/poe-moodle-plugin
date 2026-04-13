<?php
namespace local_poe;

defined('MOODLE_INTERNAL') || die();

class poe_assignment_submission
{
    public int $id;
    /**
     * @var string 'onlinetext' or 'file'
     */
    public string $type;
    public int $assignmentid;
    public string $onlinetext;
    public int $fileid;

    /**
     * @param int $id assignment submission id
     * @param string $type 'onlinetext' or 'file'
     * @param int $assignmentid assignment id
     * @param mixed $onlinetext_or_fileid onlinetext string or fileid int
     */
    public function __construct(int $id, string $type, int $assignmentid, $onlinetext_or_fileid)
    {
        $this->id = $id;
        $this->type = $type;
        $this->assignmentid = $assignmentid;
        if ($type == 'onlinetext') {
            $this->onlinetext = $onlinetext_or_fileid;
        }
        if ($type == 'file') {
            $this->fileid = $onlinetext_or_fileid;
        }
    }
}