<?php
namespace exface\ActionTest\Actions;
use exface\Core\Actions\ShowDialog;
use exface\Core\Widgets\Dialog;
use exface\Core\Widgets\AbstractWidget;
/**
 * This action shows a dialog comparing the current test result to the reference one
 * 
 * @author Andrej Kabachnik
 *
 */
class ShowDiffDialog extends ShowDialog {
	private $diff_widget_type = 'DiffText';
	
	protected function init(){
		$this->set_icon_name('compare');
		$this->set_input_rows_min(1);
		$this->set_input_rows_max(1);
		$this->set_prefill_with_filter_context(false);
	}	
	
	protected function perform(){
		// Fetch the currently saved test data
		$saved_test_data = $this->get_workbench()->data()->create_data_sheet($this->get_workbench()->model()->get_object('EXFACE.ACTIONTEST.TEST_STEP'));
		$saved_test_data->add_filter_from_string($saved_test_data->get_meta_object()->get_uid_alias(), $this->get_input_data_sheet()->get_uid_column()->get_values()[0], EXF_COMPARATOR_IN);
		$saved_test_data->get_columns()->add_from_expression('MESSAGE_CORRECT');
		$saved_test_data->get_columns()->add_from_expression('MESSAGE_CURRENT');
		$saved_test_data->get_columns()->add_from_expression('OUTPUT_CORRECT');
		$saved_test_data->get_columns()->add_from_expression('OUTPUT_CURRENT');
		$saved_test_data->get_columns()->add_from_expression('RESULT_CORRECT');
		$saved_test_data->get_columns()->add_from_expression('RESULT_CURRENT');
		$saved_test_data->get_columns()->add_from_expression('ACTION_DATA');
		$saved_test_data->get_columns()->add_from_expression('ACTION_ALIAS');
		$saved_test_data->data_read();
		
		$this->get_dialog_widget()->prefill($saved_test_data);
		
		return parent::perform();
	}
	
	protected function enhance_dialog_widget(Dialog $dialog){
		$dialog = parent::enhance_dialog_widget($dialog);
		$tabs = $this->get_called_on_ui_page()->create_widget('Tabs', $dialog);
		$tabs->add_tab($this->create_diff_widget($dialog, 'OUTPUT_CORRECT', 'OUTPUT_CURRENT', 'Output'));
		$tabs->add_tab($this->create_diff_widget($dialog, 'RESULT_CORRECT', 'RESULT_CURRENT', 'Result'));
		$tabs->add_tab($this->create_diff_widget($dialog, 'MESSAGE_CORRECT', 'MESSAGE_CURRENT', 'Message'));
		$tabs->add_tab($this->create_diff_widget($dialog, 'ACTION_DATA', 'ACTION_DATA', 'Action data'));
		$dialog->add_widget($tabs);
		return $dialog;
	}
	
	public function get_diff_widget_type() {
		return $this->diff_widget_type;
	}
	
	public function set_diff_widget_type($value) {
		$this->diff_widget_type = $value;
		return $this;
	}
	
	protected function create_diff_widget(AbstractWidget $parent, $left_attribute_alias, $rigt_attribute_alias, $caption){
		/* @var $widget \exface\Core\Widgets\DiffText */
		$widget = $this->get_called_on_ui_page()->create_widget($this->get_diff_widget_type(), $parent);
		$widget->set_left_attribute_alias($left_attribute_alias);
		$widget->set_right_attribute_alias($rigt_attribute_alias);
		$widget->set_caption($caption);
		return $widget;
	}
	  
}
?>