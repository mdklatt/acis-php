<?php
/* Unit testing for request.php.

*/

require_once 'unittest.php';
require_once 'libpath.php';
require_once 'request.php';
require_once 'error.php';


class TestRequest extends UnitTest_TestCase
{
    protected $_JSON_FILE = 'StnData.json';
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
        assert($this->_request->url == $url);
        return;
    }
    
    public function testSubmit()
    {
        list($params, $result) = $this->_request->submit($this->_params);
        assert($params == $this->_params);
        assert($result == $this->_result);
        return;
    }
    
    public function testRequestError()
    {
        $this->_params = array();
        //unset($this->_params['sid']);
        try {
            $this->_request->submit($this->_params);
        }
        catch (ACIS_RequestError $err) {
            return;
        }
        assert(false);
    }

    public function testResultError()
    {
        $this->_params['sid'] = '';
        try {
            $this->_request->submit($this->_params);
        }
        catch (ACIS_ResultError $err) {
            return;
        }
        assert(false);
    }
}


class TestStnMetaRequest extends TestRequest
{
    protected $_JSON_FILE = 'StnMeta.json';
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
        assert($result == $this->_result);
        return;
    }

    public function testRequestError()
    {
        $this->_request->location(array('bbox' =>''));
        try {
            $this->_request->submit();
        }
        catch (ACIS_RequestError $err) {
            return;
        }
        assert(false);
    }
    
    public function testResultError()
    {
        // Nothing seems to cause StnMeta to return an error.
        return;
    }
}
    

class TestStnDataRequest extends TestStnMetaRequest
{
    protected $_JSON_FILE = 'StnData.json';
    protected $_REQUEST_CLASS = 'ACIS_StnDataRequest';
    
    public function testSubmit()
    {
        $this->_request->location(array('sid'=>'okc'));
        $this->_request->meta(array('county', 'name'));
        $this->_request->dates('2011-12-31', '2012-01-01');
        $this->_request->addElement('mint', array('smry' => 'min'));
        $this->_request->addElement('maxt', array('smry' => 'max'));        
        list($params, $result) = $this->_request->submit();
        assert($result == $this->_result);
        return;
    }

    public function testRequestError()
    {
        try {
            $this->_request->submit();  // empty params
        }
        catch (ACIS_RequestError $err) {
            return;
        }
        assert(false);
    }
    
    public function testResultError()
    {
        $this->_request->location(array('sid'=>''));
        $this->_request->dates('2011-12-31', '2012-01-01');        
        try {
            $this->_request->submit();
        }
        catch (ACIS_ResultError $err) {
            return;
        }
        assert(false);
        return;
    }
}


class TestMultiStnDataRequest extends TestStnDataRequest
{
    protected $_JSON_FILE = 'MultiStnData.json';
    protected $_REQUEST_CLASS = 'ACIS_MultiStnDataRequest';
    
    public function testSubmit()
    {
        $this->_request->location(array('sids'=>'okc,tul'));
        $this->_request->meta(array('county', 'name'));
        $this->_request->dates('2011-12-31', '2012-01-01');
        $this->_request->addElement('mint', array('smry' => 'min'));
        $this->_request->addElement('maxt', array('smry' => 'max'));        
        list($params, $result) = $this->_request->submit();
        assert($result == $this->_result);
        return;
    }

    public function testRequestError()
    {
        try {
            $this->_request->submit();
        }
        catch (ACIS_RequestError $err) {
            return;
        }
        assert(false);
        return;
    }
    
    public function testResultError()
    {
        // Nothing seems to cause MultiStnData to return an error object.
        return;
    }
}
    

// Execute tests.

$suite = new UnitTest_TestSuite();
$suite->addTests('TestRequest');
$suite->addTests('TestStnMetaRequest');
$suite->addTests('TestStnDataRequest');
$suite->addTests('TestMultiStnDataRequest');
$suite->run();
