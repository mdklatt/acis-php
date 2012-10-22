<?php
/**
 * PHPUnit tests for queue.php.
 *
 * The tests can be executed using a PHPUnit test runner, e.g. the phpunit
 * command.
 */
require_once 'acis.php';
require_once 'queue.php';

class RequestQueueTest extends PHPUnit_Framework_TestCase
{
    protected $_JSON_FILE = 'data/StnData.json';
    
    protected $_query;
    protected $_request;
    
    protected function _loadData()
    {
        return json_decode(file_get_contents($this->_JSON_FILE), true);
    }

    protected function setUp()
    {
        $test_data = $this->_loadData();
        $params = $test_data['params'];
        $result = $test_data['result'];
        $this->_query = array('params' => $params, 'result' => $result);
        $this->_request = new ACIS_StnDataRequest();
        $this->_request->location(array('sid' => 'okc'));
        $this->_request->dates('2011-12-31', '2012-01-01');
        $this->_request->addElement('mint', array('smry' => 'min'));
        $this->_request->addElement(1, array('smry' => 'max'));
        $this->_request->metadata(array('county', 'name'));
        return;
    }

    public function testExecute()
    {
        $queue = new ACIS_RequestQueue();
        $queue->add($this->_request);
        $queue->add($this->_request);
        $queue->execute();
        foreach($queue->results as $item)
        {
            $this->assertEquals($this->_query['result'], $item['result']);
        }
        return;
    }

    public function testExecuteResult()
    {
        $queue = new ACIS_RequestQueue();
        $queue->add($this->_request, 'ACIS_StnDataResult');
        $queue->add($this->_request, 'ACIS_StnDataResult');
        $queue->execute();
        $result = new ACIS_StnDataResult($this->_query);
        foreach($queue->results as $item)
        {
            $this->assertEquals($result->meta, $item->meta);
            $this->assertEquals($result->data, $item->data);
            $this->assertEquals($result->smry, $item->smry);
        }
        return;
    }
}
