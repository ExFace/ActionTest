<?php
namespace exface\ActionTest\Actions;

use exface\Core\CommonLogic\AbstractAction;
use exface\Core\CommonLogic\Contexts\ContextActionTrait;

/**
 * This action switches on the record mode in the ActionTest context
 *
 * @author Andrej Kabachnik
 *        
 */
class RecordingStart extends AbstractAction
{
    use ContextActionTrait;
    
    private $skip_page_ids = array();

    protected function init()
    {
        $this->setIconName('record');
        $this->setContextScope('window');
        $this->setContextAlias('exface.ActionTest.ActionTestContext');
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
            $this->skip_page_ids = explode(',', $value);
        }
        return $this;
    }
}
?>