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
require_once '_misc.php';
require_once 'call.php';
require_once 'exception.php';


/**
 * Abstract base class for all CSV output.
 *
 * CSV records can be streamed one at a time, which might be useful for large 
 * requests. Streaming is implemented using the Iterator interface, e.g. as 
 * part of a foreach loop.
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
     */
    public function __construct($call_type)
    {       
        $this->_call = new ACIS_WebServicesCall($call_type);
        return;
    }
    
    /**
     * Clean up an _ACIS_CsvStream object.
     */
    public function __destruct()
    {
        @fclose($this->_stream);
        return;
    }

    /**
     * Set the interval for this request.
     *
     * The default interval is daily ('dly')
     */ 
    public function interval($value)
    {
        $this->_interval = ACIS_validInterval($value);
        return;
    }

    /**
     * Return the requested element names.
     *
     * Duplicate names will be indexed, e.g. maxt0, maxt1.
     */
    public function elems()
    {
        $aliases = array();
        foreach ($this->_params['elems'] as $elem) {
            $aliases[] = $elem['alias'];
        }
        return ACIS_annotate($aliases);
    }
    
    /**
     * Add an element to this request.
     */
    public function addElement($ident, $options=array())
    {
        $elem = array_merge(ACIS_makeElement($ident), $options);
        $this->_params['elems'][] = $elem;
        return;
    }
    
    /**
     * Clear all elements from this request.
     */
    public function clearElements()
    {
        $this->_params['elems'] = array();
        return;
    }   
    
    /**
     * Reset the stream.
     */
    public function rewind()
    {
        // This is called to initialize the iterator, e.g. at the start of a
        // foreach loop, so use it to make the ACIS call, check for errors, and
        // advance to the first line of data.
        foreach ($this->_params['elems'] as &$elem) {
            $elem['interval'] = $this->_interval;
        }
        $this->_stream = $this->_call->execute($this->_params);
        $this->next();
        if (!$this->_stream) {
            @fclose($stream);
            list(, $message) = error_get_last();
            throw new RuntimeException($message);
        }
        if (substr($this->_current, 0, strlen('error')) === 'error') {
            list(, $message) = explode(':', $this->_current, 2);
            throw new ACIS_RequestError(trim($message));
        }
        $this->_header();
        return;
    }

    /**
     * Read the stream header.
     *
     * Derived classes should override this if the stream contains any header
     * information. The stream must be advanced to the first line of data. 
     */
    protected function _header() { return; }
    
    /**
     * Retrieve the next line of text from the server.
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
     * Return true or not the stream is in a valid state.
     */
    public function valid()
    {
        return $this->_stream != null;
    }

    /**
     * Return the key for the current element.
     *
     * This is a required part of the Iterator interface, but keys have no
     * meaning in this context so null is returned.
     */
    public function key() { return null; }
}

/** 
 * A StnData stream.
 */
class ACIS_StnDataStream extends _ACIS_CsvStream
{
    private $_sid;
    
    /**
     * Construct an ACIS_StnDataStream object.
     */
    public function __construct()
    {
        parent::__construct('StnData');
        return;
    }
    
    /**
     * Set the location options for this request.
     *
     * StnData only accepts a single "sid" or "uid" parameter.x
     */
     public function location($options) 
     {
        foreach (array('sid', 'uid') as $key) {
            if ($location = ACIS_arrayGetKey($options, $key)) {
                $this->_sid = $this->_params[$key] = $location;
                return;
            }
        }
        throw new ACIS_RequestError('StnData requires uid or sid');
     }
    
    /**      
     * Set the date range (inclusive) for this request.
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
        foreach (ACIS_dateParams($sdate, $edate) as $key => $value) {
            $this->_params[$key] = $value;
        }
        return;
    }
    
    /**
     * Get header information from the stream.
     */
    protected function _header()
    {
        // First line is the site name.
        $this->meta[$this->_sid] = array('name' => $this->_current);
        $this->next();
        return;
    }

    /**
     * Process the current line of text.
     *
     * Return a record of the form (sid, date, elem1, ...). 
     */
    public function current()
    {
        $record = explode(',', $this->_current);
        array_splice($record, 0, 0, array($this->_sid));
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
     * Set the date for this request.
     *
     * MultStnData only accepts a single date for CSV output. Acceptable date
     * formats are YYYY-[MM-[DD]] (hyphens are optional but leading zeroes
     * are not; no two-digit years).
     */
    public function date($date)
    {
        if (strcasecmp('por', $date) == 0) {
            throw new ACIS_RequestException('invalid use of POR');
        }
        foreach (ACIS_dateParams($date) as $key => $value) {
            $this->_params[$key] = $value;
        }
        return;
    }
    
    /**
     * Process the current line of text.
     *
     * Read the metadata for this site and return a record of the form (sid,
     * date, elem1, ...). Not all metadata fields are present for all sites.
     */
    public function current()
    {
        $record = explode(',', $this->_current);    
        list($sid, $name, $state, $lon, $lat, $elev) = $record;
        $this->meta[$sid] = array('name' => $name, 'state' => $state);
        if ($elev != null) {
            $this->meta[$sid]['elev'] = (float)$elev;
        }
        if ($lon != null and $lat != null) {
            $this->meta[$sid]['ll'] = array((float)$lon, (float)$lat);
        }
        $elements = array_slice($record, 6);
        $record = array_merge(array($sid, $this->_params['date']), $elements);
        return $record;
    }
}
