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
 */
require_once 'date.php';
require_once 'exception.php';


/**
 * Abstract base class for all result object.
 */
abstract class _ACIS_JsonResult
{
    private $_elems = array();
    
    /**
     * Return the requested element names.
     *
     * Duplicate names will be indexed, e.g. maxt0, maxt1.
     */
    public function elems()
    {
        // For compatibility this is a function instead of a simple attribute.
        return $this->_elems;
    }
    
    /**
     * Initialize an _ACIS_JsonResult object.
     */
    protected function __construct($query)
    {
        if (!array_key_exists('params', $query)) {
            throw new InvalidArgumentException("missing params value");
        }
        if (!array_key_exists('result', $query)) {
            throw new InvalidArgumentException("missing result value");
        }
        if (array_key_exists('error', $query['result'])) {
            throw new ACIS_ResultException($query['result']['error']);
        }
        
        // Define elements.
        if (($elems = @$query['params']['elems'])) {
            $aliases = array();
            foreach (array_map('ACIS_makeElement', $elems) as $elem) {
                $aliases[] = $elem['alias'];
            }
            $this->_elems = ACIS_annotate($aliases);
        }
        else {
            $this->_elems = array();
        }
        return;
    }
}

/**
 * A result from a StnMeta call.
 *
 * The meta attribute is an associative aray keyed to the ACIS site UID, so
 * this field must be included in the result metadata.
 */
class ACIS_StnMetaResult extends _ACIS_JsonResult
{
    public $meta = array();

    /**
     * Initialize an ACIS_StnMetaResult object.
     */
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


/**
 * Abstract base class for station data results.
 *
 * The data, meta, and smry attributes are associative arrays that are keyed to
 * the ACIS site UID, so this field must be included in the result metadata.
 */
abstract class _ACIS_DataResult extends _ACIS_JsonResult 
    implements Countable, Iterator
{
    public $meta = array();
    public $data = array();
    public $smry = array();
    public $elems = array();

    /**
     * Initialize an _ACIS_DataResult object.
     */
    public function __construct($query)
    {
        parent::__construct($query);
        return;
    }

    // This is the implementation of the Countable interface.

    /**
     * Return the number of records in this result.
     */
    public function count()
    {
        $count = 0;
        foreach ($this->data as $arr) {
            $count += count($arr);
        }
        return $count;
    }


    // This is the implementation of the Iterator interface, which operates on
    // the data attribute. This allows a result object to be used in a foreach
    // statement, for example. This is a 2D array where the row index is the
    // site (keyed by the ACIS site UID), and each column is a data record 
    // ("groupby" results are not currently supported). Array traversal is
    // column-first. Derived classes must implement the current() method.

    protected $_siteIter; 
    protected $_dataIter;

    /**
     * Return the current array key.
     *
     * This is the current site UID and thus is not unique.
     */
    public function key() 
    {
        return $this->_siteIter->key();
    }

    /**
     * Advance to the next record.
     */
    public function next()
    {
        $this->_dataIter->next();
        if (!$this->_dataIter->valid()) {
            // No more records for this site so advance to the next one.
            $this->_siteIter->next();
            $this->_dataIter = $this->_siteIter->getChildren();
        }
        return;
    }

    /**
     * Return true if the iterator points to a valid array index.
     */
    public function valid()
    {
        return $this->_siteIter->valid() and $this->_dataIter->valid();
    }

    /**
     * Reset the iterator to the first record.
     */
    public function rewind()
    {
        $this->_siteIter = new RecursiveArrayIterator($this->data);
        $this->_dataIter = $this->_siteIter->getChildren();
        return;
    }

}


/**
 * A result from a StnData call.
 */
class ACIS_StnDataResult extends _ACIS_DataResult
{
    /**
     * Initialize an ACIS_StnDataResult object.
     */
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
        $this->data[$uid] = ($data = @$result['data']) ? $data : array();
        $this->smry[$uid] = ($smry = @$result['smry']) ? $smry : array();
        return;
    }

    /**
     * Return the current record.
     *
     * The returned value is an array of the form (uid, date, elem1, ...)
     */
    public function current()
    {
        $record = $this->_dataIter->current();
        array_unshift($record, $this->key());  // prepend uid
        return $record;
    }
}


/**
 * A result from a MultiStnData call.
 */
class ACIS_MultiStnDataResult extends _ACIS_DataResult
{
    private $_dateIter;

    /**
     * Initialize an ACIS_MultiStnDataResult object.
     */
    public function __construct($query)
    {
        parent::__construct($query);
        list($sdate, $edate, $interval) = ACIS_dateSpan($query['params']);
        $dates = ACIS_dateRange($sdate, $edate, $interval);
        $this->_dateIter = new InfiniteIterator(new ArrayIterator($dates));
        foreach ($query['result']['data'] as $site) {  // construct meta
            if (!array_key_exists('uid', $site['meta'])) {
                $message = 'metadata does not contain uid';
                throw new ACIS_ResultException($message);
            }
            $uid = $site['meta']['uid'];
            unset ($site['meta']['uid']);
            $this->meta[$uid] = $site['meta'];
            // For single-date requests MultiStnData returns the one record for
            // each site as a 1D array instead of a 2D array. (StnData returns
            // a 2D array no matter what.)
            if (count($dates) == 1 and array_key_exists($site, 'data')) {
                $site['data'] = array($site['data']);
            }
            $this->data[$uid] = ($data = @$site['data']) ? $data : array();
            $this->smry[$uid] = ($smry = @$site['smry']) ? $smry : array();
        }
        return;
    }

    /**
     * Return the current record.
     *
     * The returned value is an array of the form (uid, date, elem1, ...)
     */
    public function current()
    {
        $record = $this->_dataIter->current();
        array_unshift($record, $this->_dateIter->current());
        array_unshift($record, $this->key());
        return $record;
    }

    /**
     * Advance to the next record.
     */
    public function next()
    {
        // The number of records for every site is equal to the number of 
        // dates, so _dateIter will automatically rewind when advancing to the
        // next site because it's an InfiniteIterator.
        parent::next();
        $this->_dateIter->next();
        return;
    }
    
    /**
     * Reset the iterator to the first record.
     */
    public function rewind()
    {
        parent::rewind();
        $this->_dateIter->rewind();
        return;
    }
}

