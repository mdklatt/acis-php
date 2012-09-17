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

	private $_dataPos;

    public function __construct($query)
    {
    	parent::__construct($query);
		$this->_dataPos = new stdClass();
		$this->rewind();  // initialize internal array pointer
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

	public function current()
	{
		/* Return the current array value. */

		if (!$this->valid()) {
			return null;
		}
		return $this->data[$this->key()][$this->_dataPos->j];

	}

	public function key()
	{
		/* Return the current array key. */

        if (!$this->valid()) {
            return null;
        }
		$keys = array_keys($this->data);
		return $keys[$this->_dataPos->i];
	}

	public function next()
	{
		/* Set the array position to the next element. */

		if (!$this->valid()) {
			return;
		}
		if (++$this->_dataPos->j >= count($this->data[$this->key()])) {
			++$this->_dataPos->i;
			$this->_dataPos->j = 0;
		}
		return;
	}

	public function valid()
	{
		/* Return true if the end of the array hasn't been reached. */

		return $this->_dataPos->i < count($this->data);
	}

	public function rewind()
	{
		/* Reset the array position to the first element. */

		$this->_dataPos->i = $this->_dataPos->j = 0;
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
		/* Return the current record as an associative array. */

		if (!($record = parent::current())) {
			return null;
		}
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
        foreach ($query['result']['data'] as $site) {
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
		$this->_dateIter = new ArrayIterator(ACIS_dateRange($query['params']));
        return;
    }

	public function current()
	{
        if (!($record = parent::current())) {
			return null;
		}
		array_unshift($record, $this->_dateIter->current());
		array_unshift($record, $this->key());
        return $record;
	}

	public function next()
	{
        // Keep date iterator in sync with date.
		$uid = $this->key();
		parent::next();
		if (!$this->valid()) {
			return;
		}
		if ($this->key() == $uid) {
            $this->_dateIter->next();
		}
		else {  // next site
        	$this->_dateIter->rewind();
		}
		return;
	}
}
