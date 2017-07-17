<?php
namespace exface\ActionTest\Actions;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Factories\ActionFactory;
use exface\Core\CommonLogic\NameResolver;
use exface\Core\CommonLogic\AbstractAction;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\Interfaces\Exceptions\ErrorExceptionInterface;
use exface\Core\CommonLogic\Constants\Icons;
use exface\ActionTest\ActionTestApp;

/**
 * This action runs one or more selected test steps
 * 
 * @method ActionTestApp getApp()
 *
 * @author Andrej Kabachnik
 *        
 */
class RunTest extends AbstractAction
{

    private $called_in_template = null;

    protected function init()
    {
        $this->getApp()->startProfiler();
        $this->setIconName(Icons::PLAY);
        $this->setInputRowsMin(1);
        $this->setInputRowsMax(null);
    }

    protected function perform()
    {
        $total_errors = 0;
        $total_warnings = 0;
        
        // Fetch the currently saved test data
        /* @var $saved_test_data \exface\Core\CommonLogic\DataSheets\DataSheet */
        $columns = array(
            'MESSAGE_CORRECT',
            'OUTPUT_CORRECT',
            'RESULT_CORRECT',
            'DURATION_CORRECT',
            'ACTION_DATA',
            'ACTION_ALIAS',
            'IGNORE_DIFFS'
        );
        $saved_test_data = $this->getApp()->getTestStepsData($this->getInputDataSheet(), $columns);
        
        // Create a result data sheet
        $result = $this->getWorkbench()->data()->createDataSheet($saved_test_data->getMetaObject());
        // Run a test for each row of the saved data and save the test result to the result data sheet
        foreach ($saved_test_data->getRows() as $row_number => $row_data) {
            $diffs_in_output = 0;
            $diffs_in_result = 0;
            $diffs_in_message = 0;
            $errors = 0;
            $warnings = 0;
            $error_messages = array();
            
            // Instantiate the action and get the current results
            $action = ActionFactory::create($this->getWorkbench()->createNameResolver($row_data['ACTION_ALIAS'], NameResolver::OBJECT_TYPE_ACTION), null, UxonObject::fromJson($row_data['ACTION_DATA']));
            
            // Restore the exact environment from the recording
            $this->prepareEnvironment($action);
            
            // Run the action
            try {
                $new_message = $action->getResultMessage();
                $new_output = $this->getApp()->prettify($action->getResultOutput());
                $new_result_string = $action->getResultStringified();
            } catch (ErrorExceptionInterface $e) {
                $errors ++;
                $error_messages[] = $e->getMessage();
            }
            
            // Revert back to the environment of the test
            $this->revertEnvironment();
            
            // Compare to the correct results from the last accepted run
            if (! $row_data['IGNORE_DIFFS']) {
                if ($new_message != $saved_test_data->getCellValue('MESSAGE_CORRECT', $row_number)) {
                    $errors ++;
                    $diffs_in_message = 1;
                }
                if ($new_output != $saved_test_data->getCellValue('OUTPUT_CORRECT', $row_number)) {
                    $errors ++;
                    $diffs_in_output = 1;
                }
                if ($new_result_string != $saved_test_data->getCellValue('RESULT_CORRECT', $row_number)) {
                    $errors ++;
                    $diffs_in_result = 1;
                }
            }
            
            // Mark the test as OK or not
            if ($errors == 0) {
                $result->setCellValue('OK_FLAG', $row_number, 1);
            } else {
                $result->setCellValue('OK_FLAG', $row_number, 0);
            }
            
            $total_errors += $errors;
            $total_warnings += $warnings;
            
            // Update the test data with the current result
            // First copy the system fields (like the UID)
            foreach ($saved_test_data->getColumns()->getSystem()->getAll() as $col) {
                $result->setCellValue($col->getName(), $row_number, $col->getCellValue($row_number));
            }
            // Then the actual data
            $result->setCellValue('MESSAGE_CURRENT', $row_number, $new_message);
            $result->setCellValue('OUTPUT_CURRENT', $row_number, $new_output);
            $result->setCellValue('RESULT_CURRENT', $row_number, $new_result_string);
            $result->setCellValue('DIFFS_IN_MESSAGE_FLAG', $row_number, $diffs_in_message);
            $result->setCellValue('DIFFS_IN_OUTPUT_FLAG', $row_number, $diffs_in_output);
            $result->setCellValue('DIFFS_IN_RESULT_FLAG', $row_number, $diffs_in_result);
            $result->setCellValue('ERRORS_COUNT', $row_number, count($error_messages));
            $result->setCellValue('ERROR_TEXT', $row_number, implode("\n", $error_messages));
            
            // Add performance monitor data
            $duration = $this->getApp()->getProfiler()->getActionDuration($action);
            $result->setCellValue('DURATION_CURRENT', $row_number, $duration);
        }
        
        // Save the result and output a message for the user
        $result->dataUpdate();
        $this->setResultDataSheet($result);
        $this->setResult('');
        $this->setResultMessage($saved_test_data->countRows() . ' test(s) run: ' . $total_errors . ' errors, ' . $total_warnings . ' warnings');
        
        return;
    }

    protected function prepareEnvironment(ActionInterface $action)
    {
        $this->called_in_template = $this->getWorkbench()->ui()->getTemplateFromRequest()->getAliasWithNamespace();
        $this->getWorkbench()->ui()->setBaseTemplateAlias($action->getTemplateAlias());
        // TODO also replace the contexts
    }

    protected function revertEnvironment()
    {
        $this->getWorkbench()->ui()->setBaseTemplateAlias($this->called_in_template);
    }
}
?>