<?php
namespace exface\ActionTest\Actions;
use exface\Core\Actions\SetContext;
/**
 * This action switches on the record mode in the ActionTest context
 * 
 * @author aka
 *
 */
class RecordingStart extends SetContext {
	private $skip_page_ids = array();
	
	protected function init(){
		$this->set_icon_name('record');
		$this->set_scope('window');
		$this->set_context_type('ActionTest');
	}	
	
	protected function perform(){
		if ($this->get_context()->is_recording()){
			$this->set_result_message('Already recording anyway!');
		} else {
			$this->get_context()->recording_start();
			$this->get_context()->set_skip_page_ids($this->get_skip_page_ids());
			$this->set_result_message('Recording of actions started!');
		}
		return;
	}
	
	public function get_skip_page_ids() {
		return $this->skip_page_ids;
	}
	
	public function set_skip_page_ids($value) {
		if ($value){
			$this->skip_page_ids = explode(',', $value);
		}
		return $this;
	}  
}
?>