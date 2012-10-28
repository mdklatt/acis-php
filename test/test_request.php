<?php
/**
 * PHPUnit tests for request.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'acis.php';


abstract class _RequestTest extends PHPUnit_Framework_TestCase
{
    protected $_JSON_FILE;
    protected $_TEST_CLASS;
    
    protected $_query;
    protected $_params;
    protected $_result;
    
    protected function _loadData()
    {
        return json_decode(file_get_contents($this->_JSON_FILE), true);
    }
 
    protected function setUp()
    {
        $testData = $this->_loadData();
        $params = $testData['params'];
        $result = $testData['result'];
        $this->_query = array('params' => $params, 'result' => $result);
        $this->_request = new $this->_TEST_CLASS();
        return;
    }    
}


class StnMetaRequestTest extends _RequestTest
{
    protected $_JSON_FILE = 'data/StnMeta.json';
    protected $_TEST_CLASS = 'ACIS_StnMetaRequest';
    
    public function testSubmit()
    {
        $this->_request->location(array('sids'=>'okc,tul'));
        $this->_request->metadata(array('county', 'name'));
        $query = $this->_request->submit();
        $this->assertEquals($query['result'], $this->_query['result']);
        return;
    }
}
    

class StnDataRequestTest extends _RequestTest
{
    protected $_JSON_FILE = 'data/StnData.json';
    protected $_TEST_CLASS = 'ACIS_StnDataRequest';
    
    public function testInterval()
    {
        $this->_request->interval('dly');
        $this->_request->interval('mly');
        $this->_request->interval('yly');
        $this->_request->interval(array(1, 0, 0));
        $this->assertTrue(true);
    }

    public function testSubmit()
    {
        $this->_request->location(array('sid'=>'okc'));
        $this->_request->metadata(array('county', 'name'));
        $this->_request->dates('2011-12-31', '2012-01-01');
        $this->_request->addElement('mint', array('smry' => 'min'));
        $this->_request->addElement(1, array('smry' => 'max'));        
        $query = $this->_request->submit();
        $this->assertEquals($query['result'], $this->_query['result']);
        return;
    }
}


class MultiStnDataRequestTest extends _RequestTest
{
    protected $_JSON_FILE = 'data/MultiStnData.json';
    protected $_TEST_CLASS = 'ACIS_MultiStnDataRequest';
    
    public function testInterval()
    {
        $this->_request->interval('dly');
        $this->_request->interval('mly');
        $this->_request->interval('yly');
        $this->_request->interval(array(1, 0, 0));
        $this->assertTrue(true);
    }

    public function testSubmit()
    {
        $this->_request->location(array('sids'=>'okc,tul'));
        $this->_request->metadata(array('county', 'name'));
        $this->_request->dates('2011-12-31', '2012-01-01');
        $this->_request->addElement('mint', array('smry' => 'min'));
        $this->_request->addElement(1, array('smry' => 'max'));        
        $query = $this->_request->submit();
        $this->assertEquals($query['result'], $this->_query['result']);
        return;
    }
}


class AreaMetaRequestTest extends _RequestTest
{
    protected $_JSON_FILE = 'data/AreaMeta.json';
    protected $_TEST_CLASS = 'ACIS_AreaMetaRequest';
    
    protected function setUp()
    {
        $testData = $this->_loadData();
        $params = $testData['params'];
        $result = $testData['result'];
        $area = $testData['area'];
        $this->_query = array('params' => $params, 'result' => $result);
        $this->_request = new $this->_TEST_CLASS($area);
        return;
    }    

    public function testSubmit()
    {
        $this->_request->location(array('state' => 'OK'));
        $this->_request->metadata(array('name'));
        $query = $this->_request->submit();
        $this->assertEquals($query['result'], $this->_query['result']);
        return;
    }
}


class GridDataRequestTest extends _RequestTest
{
    protected $_JSON_FILE = 'data/GridData.json';
    protected $_TEST_CLASS = 'ACIS_GridDataRequest';
    
    public function testInterval()
    {
        $this->_request->interval('dly');
        $this->_request->interval('mly');
        $this->_request->interval('yly');
        $this->_request->interval(array(1, 0, 0));
        $this->assertTrue(true);
    }
    
    public function testSubmit()
    {
        $this->_request->location(array('bbox' => array(-97.05,35,-97,35.05)));
        $this->_request->metadata(array('ll'));
        $this->_request->grid(1);
        $this->_request->dates('2012-01-01', '2012-01-02');
        $this->_request->addElement(1, array('smry' => 'max'));        
        $this->_request->addElement('mint', array('smry' => 'min'));
        $query = $this->_request->submit();
        $this->assertEquals($query['result'], $this->_query['result']);
        return;
    }
}