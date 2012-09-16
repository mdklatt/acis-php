<?php

require_once('stream.php');


abstract class _StreamTest extends PHPUnit_Framework_TestCase
{
    protected $_JSON_FILE;
    protected $_TEST_CLASS;
    
	protected $_stream;
    protected $_params;
    protected $_result;
    
    protected function _loadData()
    {
        return json_decode(file_get_contents($this->_JSON_FILE), true);
    }
	
    protected function setUp()
    {
        $testData = $this->_loadData();
        $this->_meta = $testData['meta'];
        $this->_records = $testData['records'];
        $this->_stream = new $this->_TEST_CLASS();
        return;
    }    
	
	public function testInit()
	{
		$this->assertEquals($this->_stream->elems(), array());
		return;
	}	
	
	public function testAddElement()
	{
		$this->_stream->addElement('mint');
		$this->assertEquals(array('mint'), $this->_stream->elems());
		$this->_stream->addElement('maxt');
		$this->assertEquals(array('mint', 'maxt'), $this->_stream->elems());
		$this->_stream->addElement('mint');  // no duplicates, overwrite
		$this->assertEquals(array('mint', 'maxt'), $this->_stream->elems());		
		return;
	}
	
	public function testDelElement()
	{
		$this->_stream->addElement('mint');
		$this->_stream->addElement('maxt');
		$this->_stream->addElement('obst');
		$this->_stream->delElement('maxt');
		$this->assertEquals(array('mint', 'obst'), $this->_stream->elems());
		$this->_stream->delElement('maxt');  // already deleted
		$this->assertEquals(array('mint', 'obst'), $this->_stream->elems());
		$this->_stream->delElement();  // delete all
		$this->assertEquals(array(), $this->_stream->elems());
		return;
	}
}


class StnDataStreamTest extends _StreamTest
{
    protected $_JSON_FILE = 'data/StnDataCsv.json';
    protected $_TEST_CLASS = 'ACIS_StnDataStream';
    
    public function testIter()
    {
    	$this->_stream->dates("2011-12-31", "2012-01-01");
    	$this->_stream->location(array("sid" => "okc"));
    	$this->_stream->addElement("mint");
    	$this->_stream->addElement("maxt");
        $i = 0;
        foreach ($this->_stream as $record) {
            $this->assertEquals($this->_records[$i], $record); 
            ++$i;
        }
        $this->assertEquals($this->_meta, $this->_stream->meta);   	
    }
}


class MultiStnDataStreamTest extends _StreamTest
{
    protected $_JSON_FILE = 'data/MultiStnDataCsv.json';
    protected $_TEST_CLASS = 'ACIS_MultiStnDataStream';

    public function testIter()
    {
    	$this->_stream->date("2011-12-31");
    	$this->_stream->location(array("sids" => "okc,okcthr"));
    	$this->_stream->addElement("mint");
    	$this->_stream->addElement("maxt");
        $i = 0;
        foreach ($this->_stream as $record) {
            $this->assertEquals($this->_records[$i], $record); 
            ++$i;
        }    	
        $this->assertEquals($this->_meta, $this->_stream->meta);   	
    }
}