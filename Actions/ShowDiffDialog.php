<?php
namespace exface\ActionTest\Actions;

use exface\Core\Actions\ShowDialog;
use exface\Core\Widgets\Dialog;
use exface\Core\Widgets\AbstractWidget;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\Constants\Icons;

/**
 * This action shows a dialog comparing the current test result to the reference one
 *
 * @author Andrej Kabachnik
 *        
 */
class ShowDiffDialog extends ShowDialog
{

    private $diff_widget_type = 'DiffText';

    protected function init()
    {
        $this->setIconName(Icons::COMPARE);
        $this->setInputRowsMin(1);
        $this->setInputRowsMax(1);
        $this->setPrefillWithFilterContext(false);
    }

    protected function perform()
    {
        // Fetch the currently saved test data
        $saved_test_data = $this->getWorkbench()->data()->createDataSheet($this->getWorkbench()->model()->getObject('EXFACE.ACTIONTEST.TEST_STEP'));
        $saved_test_data->addFilterFromString($saved_test_data->getMetaObject()->getUidAlias(), $this->getInputDataSheet()->getUidColumn()->getValues()[0], EXF_COMPARATOR_IN);
        $saved_test_data->getColumns()->addFromExpression('MESSAGE_CORRECT');
        $saved_test_data->getColumns()->addFromExpression('MESSAGE_CURRENT');
        $saved_test_data->getColumns()->addFromExpression('OUTPUT_CORRECT');
        $saved_test_data->getColumns()->addFromExpression('OUTPUT_CURRENT');
        $saved_test_data->getColumns()->addFromExpression('RESULT_CORRECT');
        $saved_test_data->getColumns()->addFromExpression('RESULT_CURRENT');
        $saved_test_data->getColumns()->addFromExpression('ACTION_DATA');
        $saved_test_data->getColumns()->addFromExpression('ACTION_ALIAS');
        $saved_test_data->dataRead();
        
        $this->getDialogWidget()->prefill($saved_test_data);
        
        return parent::perform();
    }

    protected function enhanceDialogWidget(Dialog $dialog)
    {
        $dialog = parent::enhanceDialogWidget($dialog);
        
        // Create tabs for different things to compare
        $tabs = $this->getCalledOnUiPage()->createWidget('Tabs', $dialog);
        $tabs->addTab($this->createDiffWidget($dialog, 'OUTPUT_CORRECT', 'OUTPUT_CURRENT', 'Output'));
        $tabs->addTab($this->createDiffWidget($dialog, 'RESULT_CORRECT', 'RESULT_CURRENT', 'Result'));
        $tabs->addTab($this->createDiffWidget($dialog, 'MESSAGE_CORRECT', 'MESSAGE_CURRENT', 'Message'));
        $tabs->addTab($this->createDiffWidget($dialog, 'ACTION_DATA', 'ACTION_DATA', 'Action data'));
        $dialog->addWidget($tabs);
        
        // Add the accept button
        /* @var $button \exface\Core\Widgets\DialogButton */
        $button = $dialog->createButton();
        $button->setCaption('Accept changes');
        $button->setActionAlias('exface.ActionTest.AcceptChanges');
        $button->setCloseDialogAfterActionSucceeds(true);
        $dialog->addButton($button);
        
        return $dialog;
    }

    public function getDiffWidgetType()
    {
        return $this->diff_widget_type;
    }

    public function setDiffWidgetType($value)
    {
        $this->diff_widget_type = $value;
        return $this;
    }

    protected function createDiffWidget(AbstractWidget $parent, $left_attribute_alias, $rigt_attribute_alias, $caption)
    {
        /* @var $widget \exface\Core\Widgets\DiffText */
        $widget = $this->getCalledOnUiPage()->createWidget($this->getDiffWidgetType(), $parent);
        $widget->setLeftAttributeAlias($left_attribute_alias);
        $widget->setRightAttributeAlias($rigt_attribute_alias);
        $widget->setCaption($caption);
        return $widget;
    }
}
?>