<?php
namespace exface\ActionTest\Contexts;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Events\ActionEvent;
use exface\Core\CommonLogic\Contexts\AbstractContext;
use exface\Core\CommonLogic\Constants\Colors;
use exface\Core\CommonLogic\Constants\Icons;
use exface\Core\Factories\WidgetFactory;
use exface\Core\Widgets\Container;
use exface\Core\Interfaces\NameResolverInterface;
use exface\Core\Exceptions\Contexts\ContextAccessDeniedError;
use exface\Core\Interfaces\Actions\ActionInterface;
use exface\Core\CommonLogic\DataSheets\DataSorter;

/**
 * This context shows a menu for test recording in the ContextBar
 *
 * @author Andrej Kabachnik
 *        
 */
class ActionTestContext extends AbstractContext
{

    private $recording = false;

    private $recording_test_case_id = null;

    private $recorded_steps_counter = 0;
    
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
     *
     * @return UxonObject
     */
    public function exportUxonObject()
    {
        $uxon = new UxonObject();
        if ($this->isRecording()) {
            $uxon->setProperty('recording', $this->isRecording());
            if ($this->getRecordedStepsCounter()) {
                $uxon->setProperty('recorded_steps_counter', $this->getRecordedStepsCounter());
            }
            if ($this->getRecordingTestCaseId()) {
                $uxon->setProperty('recording_test_case_id', $this->getRecordingTestCaseId());
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
        if ($uxon->hasProperty('recording')) {
            $this->recording = $uxon->getProperty('recording');
            
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
        if ($uxon->hasProperty('recording_test_case_id')) {
            $this->setRecordingTestCaseId($uxon->getProperty('recording_test_case_id'));
        }
        if ($uxon->hasProperty('recording_test_case_id')) {
            $this->setRecordingTestCaseId($uxon->getProperty('recording_test_case_id'));
        }
        if ($uxon->hasProperty('recorded_steps_counter')) {
            $this->setRecordedStepsCounter($uxon->getProperty('recorded_steps_counter'));
        }
        return $this;
    }

    public function recordAction(ActionEvent $event)
    {
        
        $action = $event->getAction();
        
        if ($this->skipAction($action)){
            return $this;
        }
        
        if ($action->getCalledByWidget()) {
            $page_id = $action->getCalledByWidget()->getPage()->getId();
        }
        if (is_null($page_id)){
            $page_id = $this->getWorkbench()->getCMS()->getPageId();
        }
        
        // Create a test case if needed
        if (! $this->getRecordingTestCaseId()) {
            $test_case_data = $this->getWorkbench()->data()->createDataSheet($this->getWorkbench()->model()->getObject('EXFACE.ACTIONTEST.TEST_CASE'));
            $test_case_data->setCellValue('NAME', 0, $this->createTestCaseName($this->getWorkbench()->getCMS()->getPageTitle($page_id)));
            $test_case_data->setCellValue('START_PAGE_ID', 0, $page_id);
            $test_case_data->setCellValue('START_PAGE_NAME', 0, $this->getWorkbench()->getCMS()->getPageTitle($page_id));
            $test_case_data->setCellValue('START_OBJECT', 0, $action->getInputDataSheet()->getMetaObject()->getId());
            $test_case_data->dataCreate();
            $this->setRecordingTestCaseId($test_case_data->getCellValue($test_case_data->getMetaObject()->getUidAttributeAlias(), 0));
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
        
        return $this;
    }
    
    /**
     * Returns TRUE if the given action should NOT be recorded and FALSE otherwise.
     * 
     * @param ActionInterface $action
     * @return boolean
     */
    protected function skipAction(ActionInterface $action)
    {
        // Do not record start/stop actions
        if ($action->is('exface.ActionTest.RecordingStart') || $action->is('exface.ActionTest.RecordingStop')){
            return true;
        }
        
        // Do not record opening the popup of the ActionTest context
        if ($action->is('exface.Core.ShowContextPopup') && $action->getContext() === $this){
            return true;
        }
        
        // Do not log ContextBar refresh actions as they occur automatically
        // after many types of requests.
        if ($action->getAliasWithNamespace() === 'exface.Core.ShowWidget' && $action->getWidget()->is('ContextBar')){
            return true;
        }
        
        return false;
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
        $test_case_object = $this->getWorkbench()->model()->getObject('exface.ActionTest.TEST_CASE');
        /* @var $table \exface\Core\Widgets\DataTable */
        $table = WidgetFactory::create($container->getPage(), 'DataTable', $container);
        $table
            ->setCaption($this->getName())
            ->setMetaObject($test_case_object)
            ->setPaginatePageSize(10)
            ->setPaginate(false)
            ->setLazyLoading(false)
            ->setNowrap(false)
            ->addColumn($table->createColumnFromAttribute($test_case_object->getLabelAttribute()))
            ->addSorter('CREATED_ON', DataSorter::DIRECTION_DESC);
        
        $table->getToolbarMain()->setIncludeSearchActions(false);
        
        // Add the REC button
        $table->addButton(
            $table->createButton()->setActionAlias('exface.ActionTest.RecordingStart')
        );
        
        // Add the STOP button
        $table->addButton(
            $table->createButton()->setActionAlias('exface.ActionTest.RecordingStop')
        );
        
        // Add the EDIT button
        $table->addButton(
            $table->createButton()->setActionAlias('exface.Core.EditObjectDialog')->setVisibility(EXF_WIDGET_VISIBILITY_OPTIONAL)
        );
        
        // Add the DELETE button
        $table->addButton(
            $table->createButton()->setActionAlias('exface.Core.DeleteObject')->setVisibility(EXF_WIDGET_VISIBILITY_OPTIONAL)
        );
        
        $container->addWidget($table);
        return $container;
    }
}
?>