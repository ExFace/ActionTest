<?php
namespace exface\ActionTest;

use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\CommonLogic\DataSheets\DataSorter;
use exface\Core\CommonLogic\Profiler;
use exface\Core\Exceptions\Actions\ActionInputInvalidObjectError;
use exface\Core\Factories\DataSheetFactory;
use exface\Core\CommonLogic\Model\App;
use exface\Core\CommonLogic\AppInstallers\SqlSchemaInstaller;
use exface\Core\Interfaces\InstallerInterface;

class ActionTestApp extends App
{
    private $profiler = null;
    
    public function getProfiler()
    {
        if (is_null($this->profiler)){
            $this->startProfiler();
        }
        return $this->profiler;
    }

    public function startProfiler()
    {
        $this->profiler = new Profiler($this->getWorkbench());
        return $this;
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
            $saved_test_data->addFilterFromString($saved_test_data->getMetaObject()->getUidAttributeAlias(), implode($saved_test_data->getMetaObject()->getUidAttribute()->getValueListDelimiter(), $input_data_sheet->getUidColumn()->getValues()), EXF_COMPARATOR_IN);
        } else {
            throw new ActionInputInvalidObjectError($this, 'Running tests is currently only support for explicitly specified test steps or test cases - "' . $input_data_sheet->getMetaObject()->getAliasWithNamespace() . '" given!', '6T5DMUS');
        }
        
        $saved_test_data->dataRead();
        
        return $saved_test_data;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Model\App::getInstaller()
     */
    public function getInstaller(InstallerInterface $injected_installer = null)
    {
        // Add the custom MODx installer
        $installer = parent::getInstaller($injected_installer);
        
        // Add the SQL schema installer for DB fixes
        $schema_installer = new SqlSchemaInstaller($this->getNameResolver());
        $schema_installer->setLastUpdateIdConfigOption('LAST_PERFORMED_MODEL_SOURCE_UPDATE_ID');
        // FIXME how to get to the MODx data connection without knowing, that is used for the model loader. The model loader could
        // theoretically use another connection?
        $schema_installer->setDataConnection($this->getWorkbench()->model()->getModelLoader()->getDataConnection());
        $installer->addInstaller($schema_installer);
        return $installer;
    }
}
?>