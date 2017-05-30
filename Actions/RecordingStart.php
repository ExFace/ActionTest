<?php
namespace exface\ActionTest\Actions;

use exface\Core\Actions\SetContext;

/**
 * This action switches on the record mode in the ActionTest context
 *
 * @author Andrej Kabachnik
 *        
 */
class RecordingStart extends SetContext
{

    private $skip_page_ids = array();

    protected function init()
    {
        $this->setIconName('record');
        $this->setScope('window');
        $this->setContextType('ActionTest');
    }

    protected function perform()
    {
        $this->setResult('');
        if ($this->getContext()->isRecording()) {
            $this->setResultMessage('Already recording anyway!');
        } else {
            $this->getContext()->recordingStart();
            $this->getContext()->setSkipPageIds($this->getSkipPageIds());
            $this->setResultMessage('Recording of actions started!');
        }
        return;
    }

    public function getSkipPageIds()
    {
        return $this->skip_page_ids;
    }

    public function setSkipPageIds($value)
    {
        if ($value) {
            $this->skip_page_ids = explode(EXF_LIST_SEPARATOR, $value);
        }
        return $this;
    }
}
?>