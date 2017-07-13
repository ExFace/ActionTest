<?php
namespace exface\ActionTest\Actions;

use exface\Core\CommonLogic\AbstractAction;
use exface\Core\CommonLogic\Contexts\ContextActionTrait;
use exface\Core\CommonLogic\Constants\Icons;

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
        $this->setIconName(Icons::STOP);
        $this->setContextScope('window');
        $this->setContextAlias('exface.ActionTest.ActionTestContext');
    }

    protected function perform()
    {
        $this->setResult('');
        if ($this->getContext()->isRecording()) {
            $this->getContext()->recordingStop();
            $this->getContext()->setSkipNextActions($this->getContext()->getSkipNextActions() + 1);
            $this->setResultMessage($this->getApp()->getTranslator()->translate('ACTION.RECORDINGSTOP.STOPPED'));
        } else {
            $this->setResultMessage($this->getApp()->getTranslator()->translate('ACTION.RECORDINGSTOP.NOT_RECORDING'));
        }
        return;
    }
}
?>