<?php
/**
 * Classes for working with ACIS JSON results.
 *
 * The goal of this module is to provide a common interface regardless of the
 * call (StnData, MultiStnData, etc.) that generated the result. If the result
 * contains metadata they will be stored as an array keyed by site identifier.
 * If a result contains data they will also be stored as an array keyed to the
 * same identifier used for the metadata. Iterating over a StnData or
 * MultiStnData result will yield data in the same format.
 *
 * These classes are designed to used with their request module counterparts,
 * this is not mandatory. A current limitation is the handling of "groupby"
 * results; see the class documentation for specifics. GridData and General
 * call results are not currently implemented.
 *
 * This implementation is based on ACIS Web Services Version 2:
 *     <http://data.rcc-acis.org/doc/>.
 *
 */
require_once 'date.php';
require_once 'exception.php';

/**
 * Abstract base class for all result object.
 *
 */
abstract class _ACIS_JsonResult
{
    private $_elems = array();
    
    public function elems()
    {
        // For compatibility this is a function instead of an attribute.
        return $this->_elems;
    }
    
    protected function __construct($query)
    {
        if (!array_key_exists('params', $query)) {
            throw new Exception("missing required params value");
        }
        if (!array_key_exists('result', $query)) {
            throw new Exception("missing required result value");
        }
        if (array_key_exists('error', $query['result'])) {
            throw new ACIS_ResultException($query['result']['error']);
        }
        
        // Define elements.
        if (($elements = @$query['params']['elems']) === null) {
            return;
        }
        if (is_string($elements)) {  // comma-delimited string
            $elements = explode(',', $elements);
        }
        elseif (is_array($elements[0])) {  // element objects
            foreach ($elements as &$elem) {
                $elem = $elem['name'];
            }
        }
        foreach ($elements as &$elem) {
            $elem = trim($elem);
        }    
        $this->_elems = ACIS_annotate($elements);
        return;
    }
}


class ACIS_StnMetaResult extends _ACIS_JsonResult
{
    public $meta = array();

    public function __construct($query)
    {
        parent::__construct($query);
        foreach ($query['result']['meta'] as $site) {
            if (!array_key_exists('uid', $site)) {
                $message = 'metadata does not contain uid';
                throw new ACIS_ResultException($message);
            }
            $uid = $site['uid'];
            unset($site['uid']);
            $this->meta[$uid] = $site;
        }
        return;
    }
}


abstract class _ACIS_DataResult extends _ACIS_JsonResult
implements Countable, Iterator
{
    public $meta = array();
    public $data = array();
    public $smry = array();
    public $fields = array();

    public function __construct($query)
    {
        parent::__construct($query);
        $elems = $query['params']['elems'];
        if (is_array($elems)) {
            foreach ($elems as $elem) {
                $this->elems[] = is_array($elem) ? $elem['name'] : $elem;
            }
        }
        else {  // comma-delimited string of element names
            $this->elems = explode(',', $elems);
        }
        return;
    }

    // This is the implementation of the Countable interface.

    public function count()
    {
        /* Return the total number of records in the $data attribute. */

        $count = 0;
        foreach ($this->data as $arr) {
            $count += count($arr);
        }
        return $count;
    }


    // This is the implementation of the Iterator interface, which operates on
    // the $data attribute. This is a 2D array where the row index is the site
    // (keyed by the ACIS site UID) and the column index is time ('groupby'
    // results are not currently supported). Array traversal is column-first.
    // Derived classes must implement the current() method.

    protected $_siteIter;
    protected $_dataIter;

    public function key() 
    {
        // The current site UID. 
        return $this->_siteIter->key();
    }

    public function next()
    {
        $this->_dataIter->next();
        if (!$this->_dataIter->valid()) {
            $this->_siteIter->next();
            $this->_dataIter = $this->_siteIter->getChildren();
        }
        return;
    }

    public function valid()
    {
        return $this->_siteIter->valid() and $this->_dataIter->valid();
    }

    public function rewind()
    {
        $this->_siteIter = new RecursiveArrayIterator($this->data);
        $this->_dataIter = $this->_siteIter->getChildren();
        return;
    }

}


class ACIS_StnDataResult extends _ACIS_DataResult
{
    public function __construct($query)
    {
        parent::__construct($query);
        $result = $query['result'];
        if (!array_key_exists('uid', $result['meta'])) {
            $message = 'metadata does not contain uid';
            throw new ACIS_ResultException($message);
        }
        $uid = $result['meta']['uid'];
        unset($result['meta']['uid']);
        $this->meta[$uid] = $result['meta'];
        $this->data[$uid] = $result['data'];
        if (array_key_exists('smry', $result)) {
            $this->smry[$uid] = $result['smry'];
        }
        return;
    }

    public function current()
    {
        $record = $this->_dataIter->current();
        array_unshift($record, $this->key());  // prepend uid
        return $record;
    }
}


class ACIS_MultiStnDataResult extends _ACIS_DataResult
{
    private $_dateIter;

    public function __construct($query)
    {
        parent::__construct($query);
        foreach ($query['result']['data'] as $site) {  // construct meta
            if (!array_key_exists('uid', $site['meta'])) {
                $message = 'metadata does not contain uid';
                throw new ACIS_ResultException($message);
            }
            $uid = $site['meta']['uid'];
            unset ($site['meta']['uid']);
            $this->meta[$uid] = $site['meta'];
            $this->data[$uid] = $site['data'];
            if (array_key_exists('smry', $site)) {
                $this->smry[$uid] = $site['smry'];
            }
        }
        list($sdate, $edate, $interval) = ACIS_dateSpan($query['params']);
        $dates = ACIS_dateRange($sdate, $edate, $interval);
        $this->_dateIter = new InfiniteIterator(new ArrayIterator($dates));
        return;
    }

    public function current()
    {
        $record = $this->_dataIter->current();
        array_unshift($record, $this->_dateIter->current());
        array_unshift($record, $this->key());
        return $record;
    }

    public function next()
    {
        parent::next();
        $this->_dateIter->next();
        return;
    }
    
    public function rewind()
    {
        parent::rewind();
        $this->_dateIter->rewind();
        return;
    }
}

