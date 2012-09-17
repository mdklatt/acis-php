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
require_once 'call.php';
require_once 'exception.php';


/**
 * Abstract base class for all request objects.
 *
 */
abstract class _ACIS_JsonRequest
{
    protected $_params = array('output' => 'json');

    protected $_call;

    public function __construct($call_type)
    {
        $this->_call = new ACIS_WebServicesCall($call_type);
        return;
    }

    public function submit()
    {
        $result = $this->_call->execute($this->_params);
        return array('params' => $this->_params, 'result' => $result);
    }
}


abstract class _ACIS_MetaRequest extends _ACIS_JsonRequest
{
    public function __construct($call_type)
    {
        parent::__construct($call_type);
        $this->_params['meta'] = array('uid');
        return;
    }

    public function metadata($items)
    {
        // TODO: Need to validate $items.
        $items[] = 'uid';  // have to have uid
        $this->_params['meta'] = array_unique($items);
        return;
    }

    public function location($options)
    {
        // TODO: Need to validate $options.
        $this->_params = array_merge($this->_params, $options);
        return;
    }
}


abstract class _ACIS_DataRequest extends _ACIS_MetaRequest
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

    public function dates($sdate, $edate=null)
    {
    	if ($edate == null) {
			if ($sdate == 'por') {
				$this->_params['sdate'] = 'por';
				$this->_params['edate'] = 'por';
			}
			else {
				$this->_params['date'] = $sdate;
			}
        }
		else {
			$this->_params['sdate'] = $sdate;
			$this->_params['edate'] = $edate;
		}
		return;
    }

    public function interval($value)
    {
    	if (!in_array($value, array('dly', 'mly', 'yly'))) {
			throw new ACIS_RequestError("invalid interval: {$value}");
		}
		$this->_interval = $value;
		return;
    }

    public function addElement($name, $options)
    {
        // TODO: Need to validate $options.
        $elem = array_merge(array("name" => $name), $options);
        $this->_params['elems'][] = $elem;
        return;
    }

    public function delElement($name=null)
    {
        if ($name === null) {
            $this->_parms['elems'] = array();
            return;
        }
        for ($pos = 0; $pos < count($this->_params['elems']); ++$pos) {
            if ($this->_params['elems'][$pos]['name'] == $name) {
                unset($this->_params['elems'][pos]);
                break;
            }
        }
        return;
    }
}


class ACIS_StnMetaRequest extends _ACIS_MetaRequest
{
    public function __construct()
    {
        parent::__construct('StnMeta');
        return $this;
    }
}


class ACIS_StnDataRequest extends _ACIS_DataRequest
{
    public function __construct()
    {
        parent::__construct('StnData');
        return $this;
    }

    public function location($options)
    {
        // Do additional validation.
        if (!array_diff($options, array('uid', 'sid'))) {
            throw new ACIS_RequestError('StnData requires uid or sid');
        }
        parent::location($options);
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

	public function dates($sdate, $edate=null)
	{
        // Do additional validation.
		if (strtolower($sdate) == 'por' || strtolower($edate) == 'por') {
			throw new ACIS_RequestError('MultStnData does not support POR');
		}
        parent::dates($sdate, $edate);
		return;
	}
}
