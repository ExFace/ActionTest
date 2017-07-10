<?php
namespace exface\ActionTest;

use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\CommonLogic\DataSheets\DataSorter;
use exface\PerformanceMonitor\PerformanceMonitor;
use exface\Core\Exceptions\Actions\ActionInputInvalidObjectError;
use exface\Core\Factories\DataSheetFactory;

class ActionTestApp extends \exface\Core\CommonLogic\AbstractApp
{

    /**
     *
     * @return PerformanceMonitor
     */
    public function getPerformanceMonitor()
    {
        if ($monitor_app = $this->getWorkbench()->getApp('exface.PerformanceMonitor')) {
            return $monitor_app->getMonitor();
        }
    }

    public function startPerformanceMonitor()
    {
        $this->getWorkbench()->getApp('exface.PerformanceMonitor');
    }

    /**
     * Formats a given sting in a way, that is easily readable and comparable.
     * Attemts to autodetect the data format (HTML, JSON, etc.)
     *
     * @param string $string            
     */
    public function prettify($string)
    {
        $string = trim($string);
        if (substr($string, 0, 1) == '<' && substr($string, - 1) == '>') {
            $output = $this->prettifyHtml($string);
        } else {
            // IDEA Add other prettifiers like JSON here
            $output = $string;
        }
        return $output;
    }

    /**
     * Indents and formats an HTML string to be easily readable.
     * Does not change or sanitize anything! It's just a prettification!
     *
     * @param string $string            
     */
    public function prettifyHtml($string)
    {
        $indenter = new \Gajus\Dindent\Indenter(array(
            'indentation_character' => '  '
        ));
        return $indenter->indent($string);
    }

    public function getTestStepsData(DataSheetInterface $input_data_sheet, array $columns_array)
    {
        $saved_test_data = DataSheetFactory::createFromObjectIdOrAlias($this->getWorkbench(), 'exface.ActionTest.TEST_STEP');
        foreach ($columns_array as $column) {
            $saved_test_data->getColumns()->addFromExpression($column);
        }
        
        if ($input_data_sheet->getMetaObject()->is('exface.ActionTest.TEST_CASE')) {
            $saved_test_data->addFilterFromString('TEST_CASE', implode($input_data_sheet->getMetaObject()->getUidAttribute()->getValueListDelimiter(), $input_data_sheet->getUidColumn()->getValues()), EXF_COMPARATOR_IN);
            $saved_test_data->getSorters()->addFromString($input_data_sheet->getMetaObject()->getAlias(), DataSorter::DIRECTION_ASC);
            $saved_test_data->getSorters()->addFromString('SEQUENCE', DataSorter::DIRECTION_ASC);
        } elseif ($input_data_sheet->getMetaObject()->is($saved_test_data->getMetaObject())) {
            $saved_test_data->addFilterFromString($saved_test_data->getMetaObject()->getUidAlias(), implode($saved_test_data->getMetaObject()->getUidAttribute()->getValueListDelimiter(), $input_data_sheet->getUidColumn()->getValues()), EXF_COMPARATOR_IN);
        } else {
            throw new ActionInputInvalidObjectError($this, 'Running tests is currently only support for explicitly specified test steps or test cases - "' . $input_data_sheet->getMetaObject()->getAliasWithNamespace() . '" given!', '6T5DMUS');
        }
        
        $saved_test_data->dataRead();
        
        return $saved_test_data;
    }
}
?>