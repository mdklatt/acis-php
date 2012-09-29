<?php
/**
 * Classes for ACIS data requests.
 *
 * This module provides a uniform interface for constructing an ACIS data
 * request and retrieving the result from the server. There is a class for each
 * type of web services call (StnData, MultiStnData, etc).
 *
 * These classes are designed to be used with their result module counterparts,
 * but this is not mandatory. GridData and General calls are not currently
 * implemented; use ACIS_WebServicesCall instead (see the call module).
 *
 * This implementation is based on ACIS Web Services Version 2:
 *     <http://data.rcc-acis.org/doc/>.
 *
 */
require_once '_misc.php';
require_once 'call.php';
require_once 'exception.php';


/**
 * Abstract base class for all request objects.
 */
abstract class _ACIS_Request
{
    protected $_call;

    /**
     * Initialize an _ACIS_Request object.
     */
    public function __construct($call_type)
    {
        $this->_call = new ACIS_WebServicesCall($call_type);
        $this->_params = array();
        return;
    }

    /**
     * Submit a request to the server.
     *
     * The return value is an associative array containing the complete query
     * consisting of the params sent to the server and the result object it
     * returned. This value can be used to initialize a Result object; see
     * result.php.
     */
    public function submit()
    {
        $result = $this->_call->execute($this->_params);
        return array('params' => $this->_params, 'result' => $result);
    }
    
    /**
     * Define the metadata fields for this request.
     *
     * The fields parameter is a single string or an array of field names.
     */
    public function metadata($fields)
    {
        if (is_string($fields)) {
            $fields = array($fields);
        }
        $this->_params["meta"] = array_unique($fields);
        return;
    }
}

/**
 * Abstract base class for spatiotemporal data requests.
 */ 
abstract class _ACIS_PlaceTimeRequest extends _ACIS_Request
{
    /**
     * Define the location options for this request.
     *
     * The options parameter is an associative array.
     */
    public function location($options)
    {
        foreach ($options as $key => $value) {
            $this->_params[$key] = $value;
        }
        return;
    }

    /**
     * Set the date range (inclusive) for this request.
     *
     * If no edate is specified sdate is treated as a single date. The
     * parameters must be a date string or "por" which means to extend to the
     * period of record in that direction. Using "por" as a single date will
     * return the entire period of record. The acceptable date string formats
     * are YYYY-[MM-[DD]] (hyphens are optional but leading zeroes are not; no
     * two-digit years).     
     */
    public function dates($sdate, $edate=null)
    {
        foreach (ACIS_dateParams($sdate, $edate) as $key => $value) {
            $this->_params[$key] = $value;
        }
        return;       
    }
}


/**
 * Abstract base class for all meterological data requests.
 */
abstract class _ACIS_DataRequest extends _ACIS_PlaceTimeRequest
{
    protected $_interval = 'dly';

    /**
     * Initialize an _ACIS_DataRequest object.
     */
    public function __construct($call_type)
    {
        parent::__construct($call_type);
        $this->_params['elems'] = array();
        return;
    }

    /**
     * Submit a request to the server.
     */
    public function submit()
    {
        // Add interval to each element before submitting request.
        foreach ($this->_params['elems'] as &$elem) {
            $elem['interval'] = $this->_interval;
        }
        return parent::submit();
    }

    /**
     * Set the interval for this request.
     *
     * The interval can be a name ("dly", "mly", or "yly") or a year, month,
     * day array. The default value is "dly".
     */ 
    public function interval($value)
    {
        $this->_interval = ACIS_validInterval($value);
        return;
    }

    /**
     * Add an element to this request.
     *
     * The options parameter is an associative array.
     */
    public function addElement($name, $options=array())
    {
        // TODO: Need to validate $options.
        $elem = array_merge(array('name' => $name), $options);
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
}


/**
 * A StnMeta reqeust.
 *
 * For compatibility with ACIS_StnMetaResult the "uid" metadata field is part
 * of every request (see result.php)
 */
class ACIS_StnMetaRequest extends _ACIS_PlaceTimeRequest
{
    /**
     * Initialize an ACIS_StnMetaRequest object.
     */    
    public function __construct()
    {
        parent::__construct('StnMeta');
        $this->_params['meta'] = array('uid');
        return;
    }
    
    /**
     * Define the metadata fields for this request.
     *
     * The fields parameter is a single string or an array of field name.
     */
    public function metadata($fields)
    {
        if (is_string($fields)) {
            $fields = array($fields);
        }
        $fields[] = 'uid';
        parent::metadata($fields);
        return;
    }

    /**
     * Define the elements for this request.
     *
     * The elements parameter is a single string or an array of element names.
     */
    public function elements($names)
    {
        if (is_string($names)) {
            $names = array($names);
        }
        $this->_params['elems'] = $names;
        return;
    }
}


/**
 * A StnData reqeust.
 *
 * For compatibility with ACIS_StnDataResult the "uid" metadata field is part
 * of every request (see result.php)
 */
class ACIS_StnDataRequest extends _ACIS_DataRequest
{
    /**
     * Initialize an ACIS_StnDataRequest object.
     */    
    public function __construct()
    {
        parent::__construct('StnData');
        $this->_params['meta'] = array('uid');
        return;
    }
    
    /**
     * Define the metadata fields for this request.
     *
     * The fields parameter is a single string or an array of field names.
     */
    public function metadata($fields)
    {
        if (is_string($fields)) {
            $fields = array($fields);
        }
        $fields[] = 'uid';
        parent::metadata($fields);
        return;
    }
}


/**
 * A MultiStnData reqeust.
 *
 * For compatibility with ACIS_MultiStnDataResult the "uid" metadata field is
 * part of every request (see result.php)
 */
class ACIS_MultiStnDataRequest extends _ACIS_DataRequest
{
    /**
     * Initialize an ACIS_MultiStnDataRequest object.
     */    
    public function __construct()
    {
        parent::__construct('MultiStnData');
        $this->_params['meta'] = array('uid');
        return;
    }

    /**
     * Define the metadata fields for this request.
     *
     * The fields parameter is a single string or an array of field names.
     */
    public function metadata($fields)
    {
        if (is_string($fields)) {
            $fields = array($fields);
        }
        $fields[] = 'uid';
        parent::metadata($fields);
        return;
    }

    /**
     * Set the date range for this request.
     *
     * MultiStnData does not accept period of record ("por").
     */ 
    public function dates($sdate, $edate=null)
    {
        if (strcasecmp('por', $sdate) == 0 or strcasecmp('por', $edate) == 0) {
            throw new ACIS_RequestException('MultStnData does not accept POR');
        }
        parent::dates($sdate, $edate);
        return;
    }
}


// Development versions--not part of public interface yet.

/**
 * A GridData request.
 */
class ACIS_GridDataRequest extends _ACIS_DataRequest
{
    /**
     * Initialize an ACIS_GridDataRequest object.
     */    
    public function __construct()
    {
        parent::__construct('GridData');
        return;
    }
 
    /**
     * Define the grid for this request.
     */
    public function grid($id)
    {
        $this->_params['grid'] = $id;
        return;
    }
}


/**
 * A General request.
 */
class ACIS_GeneralRequest extends _ACIS_Request
{
    /**
     * Initialize an ACIS_GeneralRequest object.
     */    
    public function __construct($area_type)
    {
        parent::__construct("General/{$area_type}");
        $this->_params['meta'] = 'id';
        return;
    }

    /**
     * Define the metadata fields for this request.
     *
     * The fields parameter is a single string or an array of field names.
     */
    public function metadata($fields)
    {
        if (is_string($fields)) {
            $fields = array($fields);
        }
        $fields[] = 'id';
        parent::metadata($fields);
        return;
    }
 
    /**
     * Define the state for the request.
     */
    public function state($postal)
    {
        $this->_params['state'] = $postal;
        return;
    }
}