<?php
/* Classes for working with an ACIS web services result object.

This implementation is based on ACIS Web Services Version 2:
<http://data.rcc-acis.org/doc/>.

*/

require_once 'error.php';
require_once 'date.php';

class ACIS_StnMetaResult
{
	public $meta = array();

	public function __construct($params, $result)
	{
		/* Iinitialize an ACIS_StnMetaResult object. */

		// The $params parameter is strictly for interface consistency and is
		// ignored here.
		foreach ($result['meta'] as $site) {
			if (!array_key_exists('uid', $site)) {
				throw new ACIS_ParameterError('metadata does not contain uid');
			}
			$uid = $site['uid'];
			unset ($site['uid']);
			$this->meta[$uid] = $site;
		}
		return;
	}
}


abstract class _ACIS_DataResult
implements Countable, Iterator
{
    public $meta = array();
    public $data = array();
    public $smry = array();
    public $fields;

	private $_dataPos;


    public function __construct($params)
    {
		/* Initialize an _ACIS_DataResult object. */

		$this->_dataPos = new stdClass();
		$this->rewind();  // initialize internal array pointer
		if (is_array($params['elems'])) {
			$this->fields = array();
			foreach ($params['elems'] as $elem) {
				$this->fields[] = is_array($elem) ? $elem['name'] : $elem;
			}
		}
		else {  // comma-delimited string of element names
			$this->fields = explode(',', $params['elems']);
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
	public function __construct($params, $result)
	{
		parent::__construct($params);
		if (!array_key_exists('uid', $result['meta'])) {
			throw new ACIS_ParameterError('metadata does not contain uid');
		}
		$uid = $result['meta']['uid'];
		unset($result['meta']['uid']);
		$this->meta[$uid] = $result['meta'];
		$this->data[$uid] = $result['data'];
		if (array_key_exists('smry', $result)) {
			$this->smry[$uid] =  array_combine($this->fields, $result['smry']);
		}
		return;
	}

	public function current()
	{
		/* Return the current record as an associative array. */

		if (!($record = parent::current())) {
			return null;
		}
		$fields = array_merge(array('uid', 'date'), $this->fields);
		array_unshift($record, $this->key());  // prepend uid
		return array_combine($fields, $record);
	}
}


class ACIS_MultiStnDataResult extends _ACIS_DataResult
{
    private $_dateIter;

	public function __construct($params, $result)
	{
		parent::__construct($params);
        foreach ($result['data'] as $site) {
			if (!array_key_exists('uid', $site['meta'])) {
				throw new ACIS_ParameterError('metadata does not contain uid');
			}
			$uid = $site['meta']['uid'];
			unset ($site['meta']['uid']);
			$this->meta[$uid] = $site['meta'];
			$this->data[$uid] = $site['data'];
			if (array_key_exists('smry', $site)) {
				$this->smry[$uid] = array_combine($this->fields,
					                              $site['smry']);
			}
      	}
		$this->_dateIter = new ArrayIterator(ACIS_date_range($params));
        return;
    }


	public function current()
	{
        /* Return the current record as an associative array. */

        if (!($record = parent::current())) {
			return null;
		}
		$fields = array_merge(array('uid', 'date'), $this->fields);
		array_unshift($record, $this->_dateIter->current());
		array_unshift($record, $this->key());
        return array_combine($fields, $record);
	}

	public function next()
	{
        // Keep date iterator in sync with data.
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
