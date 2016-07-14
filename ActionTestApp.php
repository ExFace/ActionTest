<?php namespace exface\ActionTest;

use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Exceptions\ActionRuntimeException;
use exface\Core\CommonLogic\DataSheets\DataSorter;
use exface\PerformanceMonitor\PerformanceMonitor;

class ActionTestApp extends \exface\Core\CommonLogic\AbstractApp {
	
	/**
	 * @return PerformanceMonitor
	 */
	public function get_performance_monitor(){
		if($monitor_app = $this->get_workbench()->get_app('exface.PerformanceMonitor')){
			return $monitor_app->get_monitor();
		}
	}
	
	public function start_performance_monitor(){
		$this->get_workbench()->get_app('exface.PerformanceMonitor');
	}
	
	/**
	 * Formats a given sting in a way, that is easily readable and comparable. Attemts to autodetect the data format (HTML, JSON, etc.)
	 * @param string $string
	 */
	public function prettify($string){
		$string = trim($string);
		if (substr($string, 0, 1) == '<' && substr($string, -1) == '>'){
			$output = $this->prettify_html($string);
		} else {
			// IDEA Add other prettifiers like JSON here
			$output = $string;
		}
		return $output;
	}
	
	/**
	 * Indents and formats an HTML string to be easily readable. Does not change or sanitize anything! It's just a prettification!
	 * @param string $string
	 */
	public function prettify_html($string){
		require_once 'Libs/autoloader.php';
		$indenter = new \Gajus\Dindent\Indenter(array('indentation_character' => '  '));
		return $indenter->indent($string);
	}
	
	public function get_test_steps_data(DataSheetInterface $input_data_sheet, array $columns_array){
		$saved_test_data = $this->get_workbench()->data()->create_data_sheet($this->get_workbench()->model()->get_object('exface.ActionTest.TEST_STEP'));
		foreach ($columns_array as $column){
			$saved_test_data->get_columns()->add_from_expression($column);
		}
		
		if (strcasecmp($input_data_sheet->get_meta_object()->get_alias_with_namespace(), 'exface.ActionTest.TEST_CASE') === 0){
			$saved_test_data->add_filter_from_string('TEST_CASE', implode(',', $input_data_sheet->get_uid_column()->get_values()), EXF_COMPARATOR_IN);
			$saved_test_data->get_sorters()->add_from_string($input_data_sheet->get_meta_object()->get_alias(), DataSorter::DIRECTION_ASC);
			$saved_test_data->get_sorters()->add_from_string('SEQUENCE', DataSorter::DIRECTION_ASC);
		} elseif ($input_data_sheet->get_meta_object()->get_id() == $saved_test_data->get_meta_object()->get_id()) {
			$saved_test_data->add_filter_from_string($saved_test_data->get_meta_object()->get_uid_alias(), implode(',', $input_data_sheet->get_uid_column()->get_values()), EXF_COMPARATOR_IN);
		} else {
			throw new ActionRuntimeException('Running tests is currently only support for explicitly specified test steps or test cases - "' . $input_data_sheet->get_meta_object()->get_alias_with_namespace() . '" given!');
		}
		
		$saved_test_data->data_read();
		
		return $saved_test_data;
	}
}
?>