<?php
/**
 * Classes for streaming ACIS CSV data. 
 *
 * A stream class is an all-in-one for constructing a CSV data request and then
 * accessing the result. CSV calls are very restricted compared to JSON calls, 
 * but the output can be streamed one record at a time rather than as a single 
 * JSON object; this can be useful for large data requests. Metadata is stored 
 * as a dict keyed to a site identifer. Data records are streamed using the 
 * iterator interface. See call.php, request.php, and result.php if a CSV 
 * request is too limited.
 *
 * This implementation is based on ACIS Web Services Version 2:
 *   <http://data.rcc-acis.org/doc/>.
 */ 
require_once 'call.php';
require_once 'error.php';


/**
 * Abstract base class for all CSV output.
 *
 * CSV records can be streamed one at a time, which might be useful for large 
 * requests. Streaming is implemented using the Iterator interface, e.g. as 
 * part of a foreach loop.
 *
 */
abstract class _ACIS_CsvStream
implements Iterator
{		
	public $meta = array();
	
	protected $_params = array('output' => 'csv', 'elems' => array());
	protected $_interval = 'dly';
	protected $_current = null;
	
	private $_stream = null;	
	private $_call;

	/**
	 * Initialize an _ACIS_CsvStream object.
	 *
	 */
	public function __construct($call_type)
	{		
		$this->_call = new ACIS_WebServicesCall($call_type);
		return;
	}
	
	/**
	 * Clean up an _ACIS_CsvStream object.
	 *
	 */
	public function __destruct()
	{
		// Close the connection to the server.
		@fclose($this->_stream);
		return;
	}

	/**
	 * Set the interval for this request.
	 *
	 * The default interval is daily ('dly')
	 *
	 */	
    public function interval($value)
    {
    	if (!in_array($value, array('dly', 'mly', 'yly'))) {
			throw ACIS_ParameterError("invalid interval: {$value}");
		}
		$this->_interval = $value;
		return;
    }

	/**
	 * Return an array of the requested element names.
	 *
	 */
	public function elems()
	{
		$elem_names = array();
		foreach ($this->_params['elems'] as $elem) {
			$elem_names[] = $elem['name'];
		}
		return $elem_names;
	}
	
	/**
	 * Add an element to this request.
	 *
	 * Adding an element that already exists will overwrite the existing
	 * element.
	 */
	public function addElement($name, $options=array())
	{
		$elements = &$this->_params['elems'];
		$new_elem = array_merge(array('name' => $name), $options);
		foreach ($elements as &$elem) {
			if ($elem['name'] == $name) {
				$elem = $new_elem;
				return;
			}
		}
		$elements[] = $new_elem;
		return;
	}
	
	/**
	 * Delete all or just "name" from the request elements.
	 *
	 */
	public function delElement($name=null)
	{
		$elements = &$this->_params['elems'];
		if ($name === null) {
			$elements = array();
			return;
		}
		for ($i = 0; $i < count($elements); ++$i) {
			if ($elements[$i]['name'] == $name) {
				unset($elements[$i]);
				$elements = array_values($elements);  // reindex array
				return;
			}
		}
		return;
	}	
	
	/**
	 * Reset the stream.
	 *
	 */
	public function rewind()
	{
		// This is called to initialize the iterator, e.g. at the start of a
		// foreach loop, so use it to make the ACIS, check for errors, and
		// advance to the first line of data.
        foreach ($this->_params['elems'] as &$elem) {
            $elem['interval'] = $this->_interval;
        }
		$call = $this->_call;  // workaround for syntax limitations
		$this->_stream = $call($this->_params);
		$this->next();
		if (!$this->_stream) {
			throw Exception("error reading from stream");
		}
		if (substr($this->_current, 0, strlen("error")) === "error") {
			list(, $message) = explode(':', $this->_current, 2);
			throw ACIS_RequestError(trim($message));
		}
		$this->_header();
		return;
	}

	/**
	 * Read the stream header.
	 *
	 * Derived classes should override this if the stream contains any header
	 * information. The stream must be advanced to the first line of data. 
	 *
	 */
	protected function _header() { return; }
	
	/**
	 * Retrieve the next line of text from the server.
	 *
	 */	
	public function next()
	{
		if (!($this->_current = trim(fgets($this->_stream)))) {
			// EOF, blank line, or error so close the stream; valid() will now
			// return false.
			fclose($this->_stream);
			$this->_stream = null;
		}
		return;
	}
	
	/**
	 * Return whether or not the stream is in a valid state.
	 *
	 */
	public function valid()
	{
		return $this->_stream != null;
	}

	/**
	 * Convert the current line of text to a record.
	 *
	 * Each derived class must implement this to return a record of the form
	 * (sid, date, elem1, ...).
	 */
	abstract public function current();

	/**
	 * Return the key for the current element.
	 *
	 * This is a required part of the Iterator interface, but keys have no
	 * meaning in this context so null is returned.
	 */
	public function key() { return null; }
}


class ACIS_StnDataStream extends _ACIS_CsvStream
{
	private $_sid;
	
	/**
	 * Construct an ACIS_StnDataStream object.
	 *
	 */
	public function __construct()
	{
		parent::__construct('StnData');
		return;
	}
	
	/**
	 * Set the location for this request.
	 *
	 * StnData only accepts a single "sid" or "uid" parameter.
	 *
	 */
	 public function location($options) 
	 {
		foreach (array('sid', 'uid') as $key) {
			if (array_key_exists($key, $options)) {
				$this->_sid = $this->_params[$key] = $options[$key];
				return;
			}
		}
		throw ACIS_RequestError('StnDataStream requires uid or sid');
	 }
	
	/**      
     * Specify the date range (inclusive) for this request.
	 *
     * If no edate is specified sdate is treated as a single date. The
     * parameters must be a date string or the value "por" which means to
     * extend to the period-of-record in that direction. Acceptable date
     * formats are YYYY-[MM-[DD]] (hyphens are optional but leading zeroes are
     * not; no two-digit years).
     *
     */
    public function dates($sdate, $edate=null)
    {
        // TODO: Need to validate dates.
        if ($edate === null) {
            if (strtolower($sdate) == "por") {  // period of record
                $this->_params["sdate"] = $this->_params["edate"] = "por";
            }
            else {  # single date
                $this->_params["date"] = sdate;
            }
        }
        else {
            $this->_params["sdate"] = $sdate;
            $this->_params["edate"] = $edate;
        }
        return;
    }
    
    /**
     * Read the stream header.
     *
     */
	protected function _header()
	{
		// First line is the site name.
		$this->meta[$this->_sid] = array("name" => $this->_current);
		$this->next();
		return;
	}

	/**
	 * Process the current line of text from the server.
	 *
	 */
	public function current()
	{
		$record = explode(',', $this->_current);
		array_unshift($record, $this->_sid);
		return $record;
	}			
}


class ACIS_MultiStnDataStream extends _ACIS_CsvStream
{
	/**
	 * Initialize an ACIS_MultiStnDataStream object.
	 *
	 */
	public function __construct()
	{
		parent::__construct('MultiStnData');
		return;
	}

	/**
	 * Set the location for this request.
	 *
	 */
	public function location($options)
	{
		// TODO: Need to validate options.
		$this->_params = array_merge($this->_params, $options);
		return;
	} 
	
	/**
	 * Specify the date for this request.
	 *
     * MultStnData only accepts a single date for CSV output. Acceptable date
     * formats are YYYY-[MM-[DD]] (hyphens are optional but leading zeroes
     * are not; no two-digit years).
     *
     */
    public function date($date)
    {
    	// TODO: Need to validate date.
        $this->_params['date'] = $date;
        return;
    }
    
    /**
     * Process the current line of text from the server.
     *
     * The meta attribute will not be fully populated until every line has been
     * received.
     *
     */
	public function current()
	{
		// The metadata for each site--name, state, lat/lon, and elevation--is 
		// part of its data record.
		$record = explode(',', $this->_current);
		list($sid, $name, $state, $lon, $lat, $elev) = $record;
		$this->meta[$sid] = array('name' => $name, 'state' => $state);
		if (is_numeric($elev)) {
			$this->meta[$sid]['elev'] = (float)$elev;
		}
		if (is_numeric($lon) && is_numeric($lat)) {
			$this->meta[$sid]['ll'] = array((float)$lon, (float)$lat);
		}
		$record = array_merge(array($sid, $this->_params['date']),
				array_slice($record, 6));
		return $record;
	}
}