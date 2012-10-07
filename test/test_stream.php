<?php
require_once 'acis.php';


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
        $this->assertEquals(array(), $this->_stream->elems());
        return;
    }    
    
    public function testElems()
    {
        $this->assertEquals(array(), $this->_stream->elems());        
        $this->_stream->addElement('mint');
        $this->_stream->addElement(1);  // maxt
        $this->assertEquals(array('mint', 'vx1'), $this->_stream->elems());
        $this->_stream->addElement('mint');  // duplicates ok
        $this->assertEquals(array('mint_0', 'vx1', 'mint_1'), 
                                                      $this->_stream->elems());
        $this->_stream->clearElements();
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
        $this->_stream->addElement(1);  // maxt
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
        $this->_stream->addElement(1);  // maxt
        $i = 0;
        foreach ($this->_stream as $record) {
            $this->assertEquals($this->_records[$i], $record); 
            ++$i;
        }        
        $this->assertEquals($this->_meta, $this->_stream->meta);
    }
}
