<?php
/**
 * PHPUnit tests for request.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'error.php';
require_once 'request.php';


class RequestTest extends PHPUnit_Framework_TestCase
{
    protected $_JSON_FILE = 'data/StnData.json';
    protected $_REQUEST_CLASS = 'ACIS_Request';
    
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
        $this->_query = $testData['query'];
        $this->_params = $testData['params'];
        $this->_result = $testData['result'];
        $this->_request = new $this->_REQUEST_CLASS($this->_query);
        return;
    }
    
    public function testUrl()
    {
        $url = implode('/', array(ACIS_Request::SERVER, $this->_query));
        $this->assertEquals($this->_request->url, $url);
        return;
    }
    
    public function testSubmit()
    {
        list($params, $result) = $this->_request->submit($this->_params);
        $this->assertEquals($params, $this->_params);
        $this->assertEquals($result, $this->_result);
        return;
    }
    
    public function testRequestError()
    {
		$this->setExpectedException('ACIS_RequestError');
        $this->_params = array();
        $this->_request->submit($this->_params);
        return;
    }

    public function testResultError()
    {
		$this->setExpectedException('ACIS_ResultError');
        $this->_params['sid'] = '';
        $this->_request->submit($this->_params);
        return;
    }
}


class StnMetaRequestTest extends RequestTest
{
    protected $_JSON_FILE = 'data/StnMeta.json';
    protected $_REQUEST_CLASS = 'ACIS_StnMetaRequest';
    
    protected function setUp()
    {
        $testData = $this->_loadData();
        $this->_query = $testData['query'];
        $this->_params = $testData['params'];
        $this->_result = $testData['result'];
        $this->_request = new $this->_REQUEST_CLASS();    
    }
    
    public function testSubmit()
    {
        $this->_request->location(array('sids'=>'okc,tul'));
        $this->_request->meta(array('county', 'name'));
        list($params, $result) = $this->_request->submit();
        $this->assertEquals($result, $this->_result);
        return;
    }

    public function testRequestError()
    {
		$this->setExpectedException('ACIS_RequestError');
        $this->_request->location(array('bbox' =>''));
        $this->_request->submit();
        return;
    }
    
    public function testResultError()
    {
        // Nothing seems to cause StnMeta to return an error.
        return;
    }
}
    

class StnDataRequestTest extends StnMetaRequestTest
{
    protected $_JSON_FILE = 'data/StnData.json';
    protected $_REQUEST_CLASS = 'ACIS_StnDataRequest';
    
    public function testSubmit()
    {
        $this->_request->location(array('sid'=>'okc'));
        $this->_request->meta(array('county', 'name'));
        $this->_request->dates('2011-12-31', '2012-01-01');
        $this->_request->addElement('mint', array('smry' => 'min'));
        $this->_request->addElement('maxt', array('smry' => 'max'));        
        list($params, $result) = $this->_request->submit();
        $this->assertEquals($result, $this->_result);
        return;
    }

    public function testRequestError()
    {
		$this->setExpectedException('ACIS_RequestError');
        $this->_request->submit();  // empty params
        return;
    }
    
    public function testResultError()
    {
		$this->setExpectedException('ACIS_ResultError');
        $this->_request->location(array('sid'=>''));
        $this->_request->dates('2011-12-31', '2012-01-01');        
        $this->_request->submit();
        return;
    }
}


class MultiStnDataRequestTest extends StnDataRequestTest
{
    protected $_JSON_FILE = 'data/MultiStnData.json';
    protected $_REQUEST_CLASS = 'ACIS_MultiStnDataRequest';
    
    public function testSubmit()
    {
        $this->_request->location(array('sids'=>'okc,tul'));
        $this->_request->meta(array('county', 'name'));
        $this->_request->dates('2011-12-31', '2012-01-01');
        $this->_request->addElement('mint', array('smry' => 'min'));
        $this->_request->addElement('maxt', array('smry' => 'max'));        
        list($params, $result) = $this->_request->submit();
        $this->assertEquals($result, $this->_result);
        return;
    }

    public function testRequestError()
    {
		$this->setExpectedException('ACIS_RequestError');
        $this->_request->submit();
        return;
    }
    
    public function testResultError()
    {
		//$this->setExpectedException('ACIS_ResultError');
        //$this->_request->location(array('bbox' =>''));
        //$this->_request->dates('2011-12-31', '2012-01-01');
        //$this->_request->addElement('mint', array('smry' => 'min'));
        //$this->_request->meta(array('county', 'name'));
        //$this->_request->submit();
        return;
    }
}
