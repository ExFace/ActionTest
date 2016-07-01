<?php
namespace exface\ActionTest\Actions;
use exface\Core\Actions\SetContext;
/**
 * This action switches on the record mode in the ActionTest context
 * 
 * @author aka
 *
 */
class RecordingStop extends SetContext {
	protected function init(){
		$this->set_icon_name('stop');
		$this->set_scope('window');
		$this->set_context_type('ActionTest');
	}	
	
	protected function perform(){
		if ($this->get_context()->is_recording()){
			$this->get_context()->recording_stop();
			$this->get_context()->set_skip_next_actions($this->get_context()->get_skip_next_actions()+1);
			$this->set_result_message('Recording of actions stopped!');
		} else {
			$this->set_result_message('Was not recording anyway!');
		}
		return;
	}
}
?>