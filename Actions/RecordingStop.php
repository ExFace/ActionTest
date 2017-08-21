<?php
namespace exface\ActionTest\Actions;

use exface\Core\CommonLogic\Contexts\ContextActionTrait;
use exface\Core\CommonLogic\Constants\Icons;

/**
 * This action switches on the record mode in the ActionTest context
 *
 * @author Andrej Kabachnik
 *        
 */
class RecordingStop extends RecordingStart
{
    use ContextActionTrait;

    protected function init()
    {
        parent::init();
        $this->setIconName(Icons::STOP);
    }

    protected function perform()
    {
        $this->setResult('');
        if ($this->getContext()->isRecording()) {
            $this->getContext()->recordingStop();
            $this->setResultMessage($this->getApp()->getTranslator()->translate('ACTION.RECORDINGSTOP.STOPPED'));
        } else {
            $this->setResultMessage($this->getApp()->getTranslator()->translate('ACTION.RECORDINGSTOP.NOT_RECORDING'));
        }
        return;
    }
}
?>