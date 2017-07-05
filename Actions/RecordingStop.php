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
class RecordingStop extends AbstractAction
{
    use ContextActionTrait;

    protected function init()
    {
        $this->setIconName('stop');
        $this->setContextScope('window');
        $this->setContextAlias('ActionTest');
    }

    protected function perform()
    {
        $this->setResult('');
        if ($this->getContext()->isRecording()) {
            $this->getContext()->recordingStop();
            $this->getContext()->setSkipNextActions($this->getContext()->getSkipNextActions() + 1);
            $this->setResultMessage('Recording of actions stopped!');
        } else {
            $this->setResultMessage('Was not recording anyway!');
        }
        return;
    }
}
?>