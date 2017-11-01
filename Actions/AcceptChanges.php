<?php
namespace exface\ActionTest\Actions;

use exface\Core\CommonLogic\AbstractAction;
use exface\Core\Interfaces\Actions\iModifyData;
use exface\Core\CommonLogic\Constants\Icons;

/**
 * This action accepts the current results of one or more actions as the new correct results
 *
 * @author Andrej Kabachnik
 *        
 */
class AcceptChanges extends AbstractAction implements iModifyData
{

    protected function init()
    {
        $this->setIcon(Icons::CHECK_CIRCLE_O);
        $this->setInputRowsMin(1);
        $this->setInputRowsMax(null);
    }

    protected function perform()
    {
        
        // Fetch the currently saved test data
        $columns = array(
            'MESSAGE_CURRENT',
            'OUTPUT_CURRENT',
            'RESULT_CURRENT',
            'DURATION_CURRENT',
            'ERRORS_COUNT'
        );
        $saved_test_data = $this->getApp()->getTestStepsData($this->getInputDataSheet(), $columns);
        
        // Create a result data sheet
        $result = $this->getWorkbench()->data()->createDataSheet($saved_test_data->getMetaObject());
        // Run a test for each row of the saved data and save the test result to the result data sheet
        foreach ($saved_test_data->getRows() as $row_number => $row_data) {
            // Add the correct values from the saved data to the result data sheet
            // First copy the system fields (like the UID)
            foreach ($saved_test_data->getColumns()->getSystem()->getAll() as $col) {
                $result->setCellValue($col->getName(), $row_number, $col->getCellValue($row_number));
            }
            // Then the actual data
            $result->setCellValue('MESSAGE_CORRECT', $row_number, $saved_test_data->getCellValue('MESSAGE_CURRENT', $row_number));
            $result->setCellValue('OUTPUT_CORRECT', $row_number, $saved_test_data->getCellValue('OUTPUT_CURRENT', $row_number));
            $result->setCellValue('RESULT_CORRECT', $row_number, $saved_test_data->getCellValue('RESULT_CURRENT', $row_number));
            $result->setCellValue('DURATION_CORRECT', $row_number, $saved_test_data->getCellValue('DURATION_CURRENT', $row_number));
            $result->setCellValue('DIFFS_IN_MESSAGE_FLAG', $row_number, 0);
            $result->setCellValue('DIFFS_IN_RESULT_FLAG', $row_number, 0);
            $result->setCellValue('DIFFS_IN_OUTPUT_FLAG', $row_number, 0);
            if ($row_data['ERRORS_COUNT'] == 0) {
                $result->setCellValue('OK_FLAG', $row_number, 1);
            }
        }
        
        // Save the result and output a message for the user
        $result->dataUpdate();
        $this->setResultDataSheet($result);
        // Set the result to an empty string, because the action does not return any visible output
        $this->setResult('');
        $this->setResultMessage('Changes for ' . $this->getInputDataSheet()->countRows() . ' test step(s) accepted!');
        
        return;
    }
}
?>