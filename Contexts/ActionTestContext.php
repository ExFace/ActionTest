<?php
namespace exface\ActionTest\Contexts;

use exface\Core\CommonLogic\Model\Object;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Events\ActionEvent;
use exface\Core\CommonLogic\Contexts\AbstractContext;
use exface\Core\CommonLogic\Constants\Colors;
use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\Factories\WidgetFactory;
use exface\Core\Widgets\Container;
use exface\Core\Interfaces\NameResolverInterface;
use exface\Core\Exceptions\Contexts\ContextAccessDeniedError;

/**
 * FIXME Use the generic DataContext instead of this ugly ActionTest specific context
 *
 * @author Andrej Kabachnik
 *        
 */
class ActionTestContext extends AbstractContext
{

    private $recording = false;

    private $recording_test_case_id = null;

    private $recorded_steps_counter = 0;

    private $skip_next_actions = 0;

    private $skip_page_ids = array();
    
    public function __construct(NameResolverInterface $name_resolver){
        parent::__construct($name_resolver);
        if ($name_resolver->getWorkbench()->context()->getScopeUser()->isUserAnonymous()){
            throw new ContextAccessDeniedError($this, 'The ActionTest context cannot be used for anonymous users!');
        }
    }

    public function recordingStart()
    {
        $this->setRecordedStepsCounter(0);
        $this->recording = true;
        return $this;
    }

    public function recordingStop()
    {
        $this->recording = false;
        return $this;
    }

    public function isRecording()
    {
        return $this->recording;
    }

    /**
     * Returns the number of upcoming actions to be skipped and not recorded.
     *
     * @return int
     */
    public function getSkipNextActions()
    {
        return $this->skip_next_actions;
    }

    /**
     * Sets the number of upcoming actions to be skipped and not recorded.
     *
     * @param int $value            
     */
    public function setSkipNextActions($number)
    {
        $this->skip_next_actions = $number;
        return $this;
    }

    /**
     *
     * @return UxonObject
     */
    public function exportUxonObject()
    {
        $uxon = $this->getWorkbench()->createUxonObject();
        if ($this->isRecording()) {
            $uxon->recording = $this->isRecording();
            if ($this->getSkipNextActions()) {
                $uxon->skip_next_actions = $this->getSkipNextActions();
            }
            if ($this->getRecordedStepsCounter()) {
                $uxon->recorded_steps_counter = $this->getRecordedStepsCounter();
            }
            if ($this->getRecordingTestCaseId()) {
                $uxon->recording_test_case_id = $this->getRecordingTestCaseId();
            }
            if ($this->getSkipPageIds()) {
                $uxon->skip_page_ids = $this->getSkipPageIds();
            }
        }
        return $uxon;
    }

    /**
     *
     * @param UxonObject $uxon            
     * @return ActionTestContext
     */
    public function importUxonObject(UxonObject $uxon)
    {
        if (isset($uxon->recording)) {
            $this->recording = $uxon->recording;
            
            // If we are recording, register a callback to record an actions output whenever an action is performed
            if ($this->isRecording()) {
                $this->getWorkbench()->eventManager()->addListener('#.Action.Perform.After', array(
                    $this,
                    'recordAction'
                ));
                // Initialize the performance monitor
                $this->getApp()->startProfiler();
            }
        }
        if (isset($uxon->skip_next_actions)) {
            $this->setSkipNextActions($uxon->skip_next_actions);
        }
        if (isset($uxon->recording_test_case_id)) {
            $this->setRecordingTestCaseId($uxon->recording_test_case_id);
        }
        if (isset($uxon->recording_test_case_id)) {
            $this->setRecordingTestCaseId($uxon->recording_test_case_id);
        }
        if (isset($uxon->recorded_steps_counter)) {
            $this->setRecordedStepsCounter($uxon->recorded_steps_counter);
        }
        if (isset($uxon->skip_page_ids)) {
            $this->setSkipPageIds($uxon->skip_page_ids);
        }
        return $this;
    }

    public function recordAction(ActionEvent $event)
    {
        if ($this->getSkipNextActions() > 0) {
            $this->setSkipNextActions($this->getSkipNextActions() - 1);
        } else {
            $action = $event->getAction();
            
            if ($action->getCalledByWidget()) {
                $page_id = $action->getCalledByWidget()->getPage()->getId();
            }
            if (is_null(page_id))
                $page_id = $this->getWorkbench()->getCMS()->getPageId();
            
            // Only continue if the current page is not the excluded list
            // var_dump($page_id, $this->getSkipPageIds());
            if (! in_array($page_id, $this->getSkipPageIds())) {
                // Create a test case if needed
                if (! $this->getRecordingTestCaseId()) {
                    $test_case_data = $this->getWorkbench()->data()->createDataSheet($this->getWorkbench()->model()->getObject('EXFACE.ACTIONTEST.TEST_CASE'));
                    $test_case_data->setCellValue('NAME', 0, $this->createTestCaseName($this->getWorkbench()->getCMS()->getPageTitle($page_id)));
                    $test_case_data->setCellValue('START_PAGE_ID', 0, $page_id);
                    $test_case_data->setCellValue('START_PAGE_NAME', 0, $this->getWorkbench()->getCMS()->getPageTitle($page_id));
                    $test_case_data->setCellValue('START_OBJECT', 0, $action->getInputDataSheet()->getMetaObject()->getId());
                    $test_case_data->dataCreate();
                    $this->setRecordingTestCaseId($test_case_data->getCellValue($test_case_data->getMetaObject()->getUidAlias(), 0));
                }
                
                // Create the test step itself
                $data_sheet = $this->getWorkbench()->data()->createDataSheet($this->getWorkbench()->model()->getObject('EXFACE.ACTIONTEST.TEST_STEP'));
                $data_sheet->setCellValue('SEQUENCE', 0, ($this->getRecordedStepsCounter() + 1));
                $data_sheet->setCellValue('TEST_CASE', 0, $this->getRecordingTestCaseId());
                $data_sheet->setCellValue('ACTION_ALIAS', 0, $action->getAliasWithNamespace());
                $data_sheet->setCellValue('ACTION_DATA', 0, $action->exportUxonObject()->toJson(true));
                $data_sheet->setCellValue('OUTPUT_CORRECT', 0, $this->getWorkbench()->getApp('exface.ActionTest')->prettify($action->getResultOutput()));
                $data_sheet->setCellValue('OUTPUT_CURRENT', 0, $this->getWorkbench()->getApp('exface.ActionTest')->prettify($action->getResultOutput()));
                $data_sheet->setCellValue('MESSAGE_CORRECT', 0, $action->getResultMessage());
                $data_sheet->setCellValue('MESSAGE_CURRENT', 0, $action->getResultMessage());
                $data_sheet->setCellValue('RESULT_CORRECT', 0, $action->getResultStringified());
                $data_sheet->setCellValue('RESULT_CURRENT', 0, $action->getResultStringified());
                if ($action->getCalledByWidget()) {
                    $data_sheet->setCellValue('WIDGET_CAPTION', 0, $action->getCalledByWidget()->getCaption());
                }
                
                // Add performance monitor data
                if ($profiler = $this->getApp()->getProfiler()) {
                    $duration = $profiler->getActionDuration($action);
                    $data_sheet->setCellValue('DURATION_CORRECT', 0, $duration);
                    $data_sheet->setCellValue('DURATION_CURRENT', 0, $duration);
                }
                
                // Add page attributes
                $data_sheet->setCellValue('PAGE_ID', 0, $page_id);
                $data_sheet->setCellValue('PAGE_NAME', 0, $this->getWorkbench()->getCMS()->getPageTitle($page_id));
                $data_sheet->setCellValue('OBJECT', 0, $action->getInputDataSheet()->getMetaObject()->getId());
                $data_sheet->setCellValue('TEMPLATE_ALIAS', 0, $action->getTemplateAlias());
                
                // Save the step to the data source
                $data_sheet->dataCreate();
                $this->setRecordedStepsCounter($this->getRecordedStepsCounter() + 1);
            }
        }
        return $this;
    }

    protected function createTestCaseName($page_name = null)
    {
        return $page_name . ' (' . date($this->getWorkbench()->getCoreApp()->getTranslator()->translate('GLOBAL.DEFAULT_DATETIME_FORMAT')) . ')';
    }

    public function getRecordingTestCaseId()
    {
        return $this->recording_test_case_id;
    }

    public function setRecordingTestCaseId($value)
    {
        $this->recording_test_case_id = $value;
        return $this;
    }

    public function getRecordedStepsCounter()
    {
        return $this->recorded_steps_counter;
    }

    public function setRecordedStepsCounter($value)
    {
        $this->recorded_steps_counter = $value;
        return $this;
    }

    public function getSkipPageIds()
    {
        return $this->skip_page_ids;
    }

    public function setSkipPageIds(array $value)
    {
        $this->skip_page_ids = $value;
        return $this;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Contexts\AbstractContext::getIcon()
     */
    public function getIcon()
    {
        return Icons::VIDEO_CAMERA;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Contexts\AbstractContext::getName()
     */
    public function getName()
    {
        return $this->getWorkbench()->getApp('exface.ActionTest')->getTranslator()->translate('CONTEXT.ACTIONTEST.NAME');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Contexts\AbstractContext::getIndicator()
     */
    public function getIndicator()
    {
        if ($this->isRecording()){
            return 'REC';
        }
        return 'OFF';
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Contexts\AbstractContext::getColor()
     */
    public function getColor()
    {
        if ($this->isRecording()){
            return Colors::RED;
        }
        return Colors::DEFAULT;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Contexts\AbstractContext::getContextBarPopup()
     */
    public function getContextBarPopup(Container $container)
    {
        /* @var $data_list \exface\Core\Widgets\Menu */
        $menu = WidgetFactory::create($container->getPage(), 'Menu', $container)
        ->setCaption($this->getName());
        
        // Add the REC button
        /* @var $button \exface\Core\Widgets\Button */
        $button = WidgetFactory::create($container->getPage(), $menu->getButtonWidgetType(), $menu)
        ->setActionAlias('exface.ActionTest.RecordingStart');
        $menu->addButton($button);
        
        // Add the STOP button
        /* @var $button \exface\Core\Widgets\Button */
        $button = WidgetFactory::create($container->getPage(), $menu->getButtonWidgetType(), $menu)
        ->setActionAlias('exface.ActionTest.RecordingStop');
        $menu->addButton($button);
        
        $container->addWidget($menu);
        return $container;
    }
}
?>