<?php
/* Classes for performing an ACIS web services request.

This implementation is based on ACIS Web Services Version 2:
<http://data.rcc-acis.org/doc/>

This requires libcurl support, which is not included by default in all PHP
builds: <http://www.php.net/manual/en/curl.setup.php>.

*/
require_once 'error.php';

class ACIS_Request
{
    /* An arbitrary ACIS request.

    */
    const SERVER = 'http://data.rcc-acis.org';
    public $url;

    public function __construct($query)
    {
        /* Initialize an ACIS_Request object.

        The $query parameter is the type of ACIS query, e.g. 'StnMeta',
        'StnData', etc.

        */
        $this->url = implode('/', array(ACIS_Request::SERVER, $query));
        return;
    }

    function submit($params)
    {
        /* Submit a request to the server.

        The $params parameter is an associative array specifying the request
        parameters. The return value is an array containing the params object
        passed to the server and the decoded JSON object returned by the
        server.

        */
        $HTTP_OK = 200;
        $HTTP_BAD = 400;
        $json_flag = !empty($params) ? 0 : JSON_FORCE_OBJECT;
    	$json = json_encode($params, $json_flag);
        $query = http_build_query(array("params" => $json));
        $conn = curl_init($this->url);
        curl_setopt($conn, CURLOPT_POST, true);
        curl_setopt($conn, CURLOPT_POSTFIELDS, $query);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, true);
        if (($result = curl_exec($conn)) === false) {
            $error = error_get_last();
            throw new Exception($error['message']);
        }
        $code = curl_getinfo($conn, CURLINFO_HTTP_CODE);
        curl_close($conn);
        if ($code != $HTTP_OK) {
            // This doesn't do the right thing for a "soft 404", e.g. an ISP
            // redirects to a custom error or search page for a DNS lookup
            // failure.
            if ($code == $HTTP_BAD) {  // ACIS plain text error message
                throw new ACIS_RequestError($result);
            }
            else {  // not an ACIS request error
                //throw new Exception(sprintf("HTTP error %d", $code));
                throw new Exception("HTTP error {$code}");
            }
        }
        $result = json_decode($result, true);
        if (array_key_exists('error', $result)) {
            throw new ACIS_ResultError($result['error']);
        }
        return array($params, $result);
    }
}

class ACIS_StnMetaRequest extends ACIS_Request
{
    protected $_QUERY = 'StnMeta';
    protected $_params = array('meta' => array('uid'));

    public function __construct()
    {
        parent::__construct($this->_QUERY);
    }

    public function submit($params=null)
    {
    	// Default argument is a hack to silence warning about mismatched
    	// signature with parent::submit(); argument is ignored here.
        return parent::submit($this->_params);
    }

    public function meta($args)
    {
        if (is_string($args)) {
            $args = explode(',', $args);
        }
        if (!in_array('uid', $args)) {
            $args[] = 'uid';
        }
        $this->_params['meta'] = $args;
        return;
    }

    public function location($options)
    {
        $this->_params = array_merge($this->_params, $options);
        return;
    }
}


class ACIS_StnDataRequest extends ACIS_StnMetaRequest
{
	protected $_QUERY = 'StnData';

	public function __construct()
	{
		parent::__construct();
		$this->clearElements();  // initialize
		$this->_interval = 'dly';
	}

	public function dates($sdate, $edate=null)
	{
		/* Set the date range for this request.

		Dates must be an ACIS-acceptable string format, i.e. YYYY[-MM[-DD]]
		(hyphens are optional) or 'por' for period-of-record. A single 'por'
		value (i.e. $edate is null) will retrieve the entire period of record.

		*/
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

	public function interval($interval)
	{
		if (!in_array($interval, array('dly', 'mly', 'yly'))) {
			throw ACIS_ParameterError("invalid interval: {$interval}");
		}
		$this->_interval = $interval;
		return;
	}

	public function addElement($name, $options=array())
	{
		$options['name'] = $name;
		$this->_params['elems'][] = $options;
		return;
	}

	public function clearElements()
	{
		$this->_params['elems'] = array();
		return;
	}
}


class ACIS_MultiStnDataRequest extends ACIS_StnDataRequest
{
	protected $_QUERY = 'MultiStnData';

	public function dates($sdate, $edate=null)
	{
		/* Set the date range for this request.

		Dates must be an ACIS-acceptable string format, i.e. YYYY[-MM[-DD]]
		(hyphens are optional). MultStnData requests do not support 'por'.

		*/
		if ($sdate == 'por' || $edate == 'por') {
			throw ACIS_ParameterError('MultStnData does not support por');
		}
		if ($edate == null) {  // single date
			$this->_params['date'] = $sdate;
        }
		else {
			$this->_params['sdate'] = $sdate;
			$this->_params['edate'] = $edate;
		}
		return;
	}
}
