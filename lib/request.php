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
 * implemented; use a WebServicesCall instead (see the call module).
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
 *
 */
abstract class _ACIS_Request
{
    protected $_call;

    public function __construct($call_type)
    {
        $this->_call = new ACIS_WebServicesCall($call_type);
        $this->_params = array();
        return;
    }

    public function submit()
    {
        $result = $this->_call->execute($this->_params);
        return array('params' => $this->_params, 'result' => $result);
    }
    
    public function metadata($fields)
    {
        $this->_params["meta"] = array_unique($fields);
        return;
    }
}


abstract class _ACIS_PlaceTimeRequest extends _ACIS_Request
{
    public function location($options)
    {
        foreach ($options as $key => $value) {
            $this->_params[$key] = $value;
        }
        return;
    }

    public function dates($sdate, $edate=null)
    {
        foreach (ACIS_dateParams($sdate, $edate) as $key => $value) {
            $this->_params[$key] = $value;
        }
		return;       
    }
}


abstract class _ACIS_DataRequest extends _ACIS_PlaceTimeRequest
{
    protected $_interval = 'dly';

    public function __construct($call_type)
    {
        parent::__construct($call_type);
        $this->_params['elems'] = array();
        return;
    }

    public function submit()
    {
        foreach ($this->_params['elems'] as &$elem) {
            $elem['interval'] = $this->_interval;
        }
        return parent::submit();
    }

    public function interval($value)
    {
		$this->_interval = ACIS_validInterval($value);
		return;
    }

    public function addElement($name, $options=array())
    {
        // TODO: Need to validate $options.
        $elem = array_merge(array("name" => $name), $options);
        $this->_params['elems'][] = $elem;
        return;
    }

    public function clearElements()
    {
        $this->_params['elems'] = array();
        return;
    }
}


class ACIS_StnMetaRequest extends _ACIS_PlaceTimeRequest
{
    public function __construct()
    {
        parent::__construct('StnMeta');
        $this->_params['meta'] = array('uid');
        return;
    }
    
    public function metadata($fields)
    {
        // $fields is a string or array
        $fields[] = 'uid';
        parent::metadata($fields);
        return;
    }

    public function elements($names)
    {
        // $names is a string or array
        $this->_params['elems'] = $names;
    }
}


class ACIS_StnDataRequest extends _ACIS_DataRequest
{
    public function __construct()
    {
        parent::__construct('StnData');
        $this->_params['meta'] = array('uid');
        return;
    }
    
    public function metadata($fields)
    {
        // $fields is a string or array
        $fields[] = 'uid';
        parent::metadata($fields);
        return;
    }
}


class ACIS_MultiStnDataRequest extends _ACIS_DataRequest
{
    public function __construct()
    {
        parent::__construct('MultiStnData');
        return;
    }

    public function metadata($fields)
    {
        // $fields is a string or array
        $fields[] = 'uid';
        parent::metadata($fields);
        return;
    }

	public function dates($sdate, $edate=null)
	{
        // Do additional validation.
		if (strcasecmp('por', $sdate) == 0 or strcasecmp('por', $edate) == 0) {
			throw new ACIS_RequestException('MultStnData does not accept POR');
		}
        parent::dates($sdate, $edate);
		return;
	}
}


// Development versions--not part of public interface yet.

class ACIS_GridData extends _ACIS_DataRequest
{
    public function __construct()
    {
        parent::__construct('GridData');
        return;
    }
 
    public function grid($id)
    {
        $this->_params['grid'] = $id;
        return;
    }
}


class ACIS_GeneralRequest extends _ACIS_Request
{
    public function __construct($area)
    {
        parent::__construct("General/{$area}");
        $this->_params['meta'] = 'id';
        return;
    }

    public function metadata($fields)
    {
        // $fields is a string or array
        $fields[] = 'id';
        parent::metadata($fields);
        return;
    }
    
    public function state($postal)
    {
        $this->_params['state'] = $postal;
    }
}