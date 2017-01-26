<?php namespace exface\ActionTest\Actions;

use exface\Core\CommonLogic\AbstractAction;
use exface\Core\Interfaces\Actions\iModifyData;

/**
 * This action accepts the current results of one or more actions as the new correct results
 * 
 * @author Andrej Kabachnik
 *
 */
class AcceptChanges extends AbstractAction implements iModifyData {
	
	protected function init(){
		$this->set_icon_name('ok');
		$this->set_input_rows_min(1);
		$this->set_input_rows_max(null);
	}	
	
	protected function perform(){
		
		// Fetch the currently saved test data
		$columns = array('MESSAGE_CURRENT', 'OUTPUT_CURRENT', 'RESULT_CURRENT', 'DURATION_CURRENT', 'ERRORS_COUNT');
		$saved_test_data = $this->get_app()->get_test_steps_data($this->get_input_data_sheet(), $columns);
		
		// Create a result data sheet
		$result = $this->get_workbench()->data()->create_data_sheet($saved_test_data->get_meta_object());
		// Run a test for each row of the saved data and save the test result to the result data sheet
		foreach ($saved_test_data->get_rows() as $row_number => $row_data){
			// Add the correct values from the saved data to the result data sheet
			// First copy the system fields (like the UID)
			foreach ($saved_test_data->get_columns()->get_system()->get_all() as $col){
				$result->set_cell_value($col->get_name(), $row_number, $col->get_cell_value($row_number));
			}
			// Then the actual data
			$result->set_cell_value('MESSAGE_CORRECT', $row_number, $saved_test_data->get_cell_value('MESSAGE_CURRENT', $row_number));
			$result->set_cell_value('OUTPUT_CORRECT', $row_number, $saved_test_data->get_cell_value('OUTPUT_CURRENT', $row_number));
			$result->set_cell_value('RESULT_CORRECT', $row_number, $saved_test_data->get_cell_value('RESULT_CURRENT', $row_number));
			$result->set_cell_value('DURATION_CORRECT', $row_number, $saved_test_data->get_cell_value('DURATION_CURRENT', $row_number));
			$result->set_cell_value('DIFFS_IN_MESSAGE_FLAG', $row_number, 0);
			$result->set_cell_value('DIFFS_IN_RESULT_FLAG', $row_number, 0);
			$result->set_cell_value('DIFFS_IN_OUTPUT_FLAG', $row_number, 0);
			if ($row_data['ERRORS_COUNT'] == 0){
				$result->set_cell_value('OK_FLAG', $row_number, 1);
			}
		}
		
		// Save the result and output a message for the user
		$result->data_update();
		$this->set_result_data_sheet($result);
		// Set the result to an empty string, because the action does not return any visible output
		$this->set_result('');
		$this->set_result_message('Changes for ' . $this->get_input_data_sheet()->count_rows() . ' test step(s) accepted!');
		
		return;
	}
}
?>