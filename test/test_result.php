<?php
/**
 * PHPUnit tests for result.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'acis.php';


abstract class _ResultTest extends PHPUnit_Framework_TestCase
{
	protected $_JSON_FILE = null;
    
    protected function _loadData()
    {
        return json_decode(file_get_contents($this->_JSON_FILE), true);
    }
}


abstract class _MetaResultTest extends _ResultTest
{
    protected $_params;
    protected $_result;
    protected $_meta;

    protected function setUp()
    {
        $testData = $this->_loadData();
        $params = $testData['params'];
    	$result = $testData['result'];
        $this->_query = array('params' => $params, 'result' => $result);
        $this->_meta = $testData['meta'];
        return;
    }

    public function testMeta()
    {
        $result = new $this->_RESULT_CLASS($this->_query);
        foreach ($result->meta as $uid => $meta) {
            $this->assertEquals($meta, $this->_meta[$uid]);
        }
        return;
    }
}


abstract class _DataResultTest extends _MetaResultTest
{
    protected $_data;
    protected $_smry;
    protected $_fields;
    protected $_records;

    protected function setUp()
    {
        $testData = $this->_loadData();
        $params = $testData['params'];
    	$result = $testData['result'];
        $this->_query = array('params' => $params, 'result' => $result);
        $this->_meta = $testData['meta'];
        $this->_data = $testData['data'];
        $this->_smry = $testData['smry'];
        $this->_records = $testData['records'];
        $this->_elems = array();
        foreach ($this->_query['params']['elems'] as $elem) {
            $this->_elems[] = $elem['name'];
        }
        return;
    }

    public function testData()
    {
        $result = new $this->_RESULT_CLASS($this->_query);
        foreach ($result->data as $uid => $data) {
            $this->assertEquals($data, $this->_data[$uid]);
        }
        return;
    }

    public function testSmry()
    {
        $result = new $this->_RESULT_CLASS($this->_query);
        $this->assertEquals($this->_smry, $result->smry);
        return;
    }

    public function testElems()
    {
        $result = new $this->_RESULT_CLASS($this->_query);
        $this->assertEquals($this->_elems, $result->elems);
    }

    public function testCount()
    {
        $result = new $this->_RESULT_CLASS($this->_query);
        $this->assertEquals(count($result), count($this->_records));
    }

    public function testIter()
    {
        $result = new $this->_RESULT_CLASS($this->_query);
        $i = 0;
        foreach ($result as $record) {
            $this->assertEquals($this->_records[$i], $record);
            ++$i;
        }
        return;
    }
}


class StnMetaResultTest extends _MetaResultTest
{
    protected $_JSON_FILE = 'data/StnMeta.json';
    protected $_RESULT_CLASS = 'ACIS_StnMetaResult';

    public function testNoUid()
    {
    	$message = 'metadata does not contain uid';
		$this->setExpectedException('ACIS_ResultException', $message);
        unset($this->_query['result']['meta'][0]['uid']);
        new $this->_RESULT_CLASS($this->_query);
    	return;
    }
}


class StnDataResultTest extends _DataResultTest
{
    protected $_JSON_FILE = 'data/StnData.json';
    protected $_RESULT_CLASS = 'ACIS_StnDataResult';

    public function testNoUid()
    {
    	$message = 'metadata does not contain uid';
		$this->setExpectedException('ACIS_ResultException', $message);
        unset($this->_query['result']['meta']['uid']);
        new $this->_RESULT_CLASS($this->_query);
        return;
    }
}


class MultiStnDataResultTest extends _DataResultTest
{
    protected $_JSON_FILE = 'data/MultiStnData.json';
    protected $_RESULT_CLASS = 'ACIS_MultiStnDataResult';

    public function testNoUid()
    {
    	$message = 'metadata does not contain uid';
		$this->setExpectedException('ACIS_ResultException', $message);
        unset ($this->_query['result']['data'][0]['meta']['uid']);
        new $this->_RESULT_CLASS($this->_query);
        return;
    }
}
