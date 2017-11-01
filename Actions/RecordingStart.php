<?php
namespace exface\ActionTest\Actions;

use exface\Core\CommonLogic\AbstractAction;
use exface\Core\CommonLogic\Contexts\ContextActionTrait;
use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\Interfaces\Actions\iModifyContext;
use exface\Core\Interfaces\Contexts\ContextManagerInterface;

/**
 * This action switches on the record mode in the ActionTest context
 *
 * @author Andrej Kabachnik
 *        
 */
class RecordingStart extends AbstractAction implements iModifyContext
{
    use ContextActionTrait;

    protected function init()
    {
        parent::init();
        $this->setIcon(Icons::CIRCLE);
        $this->setContextScope(ContextManagerInterface::CONTEXT_SCOPE_WINDOW);
        $this->setContextAlias('exface.ActionTest.ActionTestContext');
    }

    protected function perform()
    {
        $this->setResult('');
        if ($this->getContext()->isRecording()) {
            $this->setResultMessage($this->getApp()->getTranslator()->translate('ACTION.RECORDINGSTART.ALREADY_RECORDING'));
        } else {
            $this->getContext()->recordingStart();
            $this->setResultMessage($this->getApp()->getTranslator()->translate('ACTION.RECORDINGSTART.STARTED'));
        }
        return;
    }
}
?>