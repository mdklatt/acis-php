<?php
/* Unit testing for result.php.

*/

require_once 'unittest.php';
require_once 'libpath.php';
require_once 'result.php';


// Define the TestCase classes.

abstract class _TestResult extends UnitTest_TestCase
{
    protected function _loadData()
    {
        return json_decode(file_get_contents($this->_JSON_FILE), true);
    }
}


abstract class _TestMetaResult extends _TestResult
{
    protected $_params;
    protected $_result;
    protected $_meta;

    protected function setUp()
    {
        $testData = $this->_loadData();
        $this->_params = $testData['params'];
        $this->_result = $testData['result'];
        $this->_meta = $testData['meta'];
        return;
    }

    public function testMeta()
    {
        $result = new $this->_RESULT_CLASS($this->_params, $this->_result);
        foreach ($result->meta as $uid => $meta) {
            assert($meta == $this->_meta[$uid]);
        }
        return;
    }
}


class _TestDataResult extends _TestMetaResult
{
    protected $_data;
    protected $_smry;
    protected $_fields;
    protected $_records;

    protected function setUp()
    {
        $testData = $this->_loadData();
        $this->_params = $testData['params'];
        $this->_result = $testData['result'];
        $this->_meta = $testData['meta'];
        $this->_data = $testData['data'];
        $this->_smry = $testData['smry'];
        $this->_records = $testData['records'];
        $this->_fields = array();
        foreach ($this->_params['elems'] as $elem) {
            $this->_fields[] = $elem['name'];
        }
        return;
    }

    public function testData()
    {
        $result = new $this->_RESULT_CLASS($this->_params, $this->_result);
        foreach ($result->data as $uid => $data) {
            assert($data == $this->_data[$uid]);
        }
        return;
    }

    public function testSmry()
    {
        $result = new $this->_RESULT_CLASS($this->_params, $this->_result);
        foreach ($result->smry as $uid => $smry) {
            $record = array_combine($this->_fields, $this->_smry[$uid]);
            assert($smry == $record);
        }
        return;
    }

    public function testFields()
    {
        $result = new $this->_RESULT_CLASS($this->_params, $this->_result);
        assert($result->fields == $this->_fields);
    }

    public function testCount()
    {
        $result = new $this->_RESULT_CLASS($this->_params, $this->_result);
        assert(count($result) == count($this->_records));
    }

    public function testIter()
    {
        $fields = array_merge(array('uid', 'date'), $this->_fields);
        $result = new $this->_RESULT_CLASS($this->_params, $this->_result);
        $i = 0;
        foreach ($result as $record) {
            assert($record == array_combine($fields, $this->_records[$i]));
            ++$i;
        }
        return;
    }
}


class TestStnMetaResult extends _TestMetaResult
{
    protected $_JSON_FILE = 'StnMeta.json';
    protected $_RESULT_CLASS = 'ACIS_StnMetaResult';

    public function testNoUid()
    {
        unset ($this->_result['meta'][0]['uid']);
        try {
            new $this->_RESULT_CLASS($this->_params, $this->_result);
        }
        catch (ACIS_ParameterError $err) {  // expected result
            return;
        }
        assert(false);
    }
}


class TestStnDataResult extends _TestDataResult
{
    protected $_JSON_FILE = 'StnData.json';
    protected $_RESULT_CLASS = 'ACIS_StnDataResult';

    public function testNoUid()
    {
        unset ($this->_result['meta']['uid']);
        try {
            new $this->_RESULT_CLASS($this->_params, $this->_result);
        }
        catch (ACIS_ParameterError $ex) {  // expected result
            return;
        }
        assert(false);
    }
}


class TestMultiStnDataResult extends _TestDataResult
{
    protected $_JSON_FILE = 'MultiStnData.json';
    protected $_RESULT_CLASS = 'ACIS_MultiStnDataResult';

    public function testNoUid()
    {
        unset ($this->_result['data'][0]['meta']['uid']);
        try {
            new $this->_RESULT_CLASS($this->_params, $this->_result);
        }
        catch (ACIS_ParameterError $ex) {  // expected result
            return;
        }
        assert(false);
    }
}


// Execute tests.

date_default_timezone_set('America/Chicago');  // stop DateTime from bitching
$suite = new UnitTest_TestSuite();
$suite->addTests('TestStnMetaResult');
$suite->addTests('TestStnDataResult');
$suite->addTests('TestMultiStnDataResult');
$suite->run();


