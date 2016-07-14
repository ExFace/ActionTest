<?php namespace exface\ActionTest\Actions;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Exceptions\exfError;
use exface\Core\Factories\ActionFactory;
use exface\Core\CommonLogic\NameResolver;
use exface\Core\CommonLogic\AbstractAction;
use exface\Core\Interfaces\Actions\ActionInterface;

/**
 * This action runs one or more selected test steps
 * 
 * @author Andrej Kabachnik
 *
 */
class RunTest extends AbstractAction {
	private $called_in_template = null;
	
	protected function init(){
		$this->get_app()->start_performance_monitor();
		$this->set_icon_name('play');
		$this->set_input_rows_min(1);
		$this->set_input_rows_max(null);
	}	
	
	protected function perform(){
		$total_errors = 0;
		$total_warnings = 0;
		
		// Fetch the currently saved test data
		/* @var $saved_test_data \exface\Core\CommonLogic\DataSheets\DataSheet */
		$columns = array('MESSAGE_CORRECT', 'OUTPUT_CORRECT', 'RESULT_CORRECT', 'DURATION_CORRECT', 'ACTION_DATA', 'ACTION_ALIAS', 'IGNORE_DIFFS');
		$saved_test_data = $this->get_app()->get_test_steps_data($this->get_input_data_sheet(), $columns);
		
		// Create a result data sheet
		$result = $this->get_workbench()->data()->create_data_sheet($saved_test_data->get_meta_object());
		// Run a test for each row of the saved data and save the test result to the result data sheet
		foreach ($saved_test_data->get_rows() as $row_number => $row_data){
			$diffs_in_output = 0;
			$diffs_in_result = 0;
			$diffs_in_message = 0;
			$errors = 0;
			$warnings = 0;
			$error_messages = array();
			
			// Instantiate the action and get the current results
			$action = ActionFactory::create($this->get_workbench()->create_name_resolver($row_data['ACTION_ALIAS'], NameResolver::OBJECT_TYPE_ACTION), null, UxonObject::from_json($row_data['ACTION_DATA']));
			
			// Restore the exact environment from the recording
			$this->prepare_environment($action);
			
			// Run the action
			try {
				$new_message = $action->get_result_message();
				$new_output = $this->get_app()->prettify($action->get_result_output());
				$new_result_string = $action->get_result_stringified();
			} catch (exfError $e) {
				$errors++;
				$error_messages[] = $e->getMessage();
			}
			
			// Revert back to the environment of the test
			$this->revert_environment();
			
			// Compare to the correct results from the last accepted run
			if (!$row_data['IGNORE_DIFFS']){
				if ($new_message != $saved_test_data->get_cell_value('MESSAGE_CORRECT', $row_number)){
					$errors++;
					$diffs_in_message = 1;
				}
				if ($new_output != $saved_test_data->get_cell_value('OUTPUT_CORRECT', $row_number)){
					$errors++;
					$diffs_in_output = 1;
				}
				if ($new_result_string != $saved_test_data->get_cell_value('RESULT_CORRECT', $row_number)){
					$errors++;
					$diffs_in_result = 1;
				}
			}
			
			// Mark the test as OK or not
			if ($errors == 0){
				$result->set_cell_value('OK_FLAG', $row_number, 1);
			} else {
				$result->set_cell_value('OK_FLAG', $row_number, 0);
			}
			
			$total_errors += $errors;
			$total_warnings += $warnings;
			
			// Update the test data with the current result
			// First copy the system fields (like the UID)
			foreach ($saved_test_data->get_columns()->get_system()->get_all() as $col){
				$result->set_cell_value($col->get_name(), $row_number, $col->get_cell_value($row_number));
			}
			// Then the actual data
			$result->set_cell_value('MESSAGE_CURRENT', $row_number, $new_message);
			$result->set_cell_value('OUTPUT_CURRENT', $row_number, $new_output);
			$result->set_cell_value('RESULT_CURRENT', $row_number, $new_result_string);
			$result->set_cell_value('DIFFS_IN_MESSAGE_FLAG', $row_number, $diffs_in_message);
			$result->set_cell_value('DIFFS_IN_OUTPUT_FLAG', $row_number, $diffs_in_output);
			$result->set_cell_value('DIFFS_IN_RESULT_FLAG', $row_number, $diffs_in_result);
			$result->set_cell_value('ERRORS_COUNT', $row_number, count($error_messages));
			$result->set_cell_value('ERROR_TEXT', $row_number, implode("\n", $error_messages));
			
			// Add performance monitor data
			$duration = $this->get_app()->get_performance_monitor()->get_action_duration($action);
			$result->set_cell_value('DURATION_CURRENT', $row_number, $duration);
		}
		
		// Save the result and output a message for the user
		$result->data_update();
		$this->set_result_data_sheet($result);
		$this->set_result('');
		$this->set_result_message($saved_test_data->count_rows() . ' test(s) run: ' . $total_errors . ' errors, ' . $total_warnings . ' warnings');
		
		return;
	}
	
	protected function prepare_environment(ActionInterface $action){
		$this->called_in_template = $this->get_workbench()->ui()->get_template_from_request()->get_alias_with_namespace();
		$this->get_workbench()->ui()->set_base_template_alias($action->get_template_alias());
		// TODO also replace the contexts
	}
	
	protected function revert_environment(){
		$this->get_workbench()->ui()->set_base_template_alias($this->called_in_template);
	}
}
?>