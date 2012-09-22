<?php
/** 
 * Execute ACIS Web Services calls.
 *
 * This is the core library module. The WebServicesCall is all that is needed
 * to execute an ACIS Web Services call, and can be uses in cases where the
 * Request, Result, or Stream classes do not have the needed functionality. In 
 * particular a WebServicesCall is necessary for GridData and General calls.
 *
 * This implementation is based on ACIS Web Services Version 2:
 *     <http://data.rcc-acis.org/doc/>.
 *
 */
require_once 'exception.php';


/**
 * An ACIS Web Services call.
 *
 * This class handles the encoding of the params object, the HTTP request and
 * error handling, and decoding of the returned result.
 *
 */
class ACIS_WebServicesCall
{
    const _SERVER = 'http://data.rcc-acis.org';
    
    public $url;
    
    public function __construct($call_type)
    {
        $this->url = implode('/', array(ACIS_WebServicesCall::_SERVER, 
                $call_type));
        return;
    }

	/**
     * Execute a web services call.
     *
     * This is a syntactic shortcut for invoking execute() as a functor, i.e. 
     * $obj($params) => $obj->execute($params). 
     */
	public function __invoke($params)
	{
		return $this->execute($params);
	}

	/**
	 * Execute a web services call.
     *
	 * The params parameter is an associative array specifying the call 
	 * parameters. The result depends on the output type specified in params. 
	 * JSON output (the default) gets decoded and returned as an associative
	 * array, and for all other output types a stream resource gets returned.
     *
	 */
    public function execute($params)
    {
        $json_flag = !empty($params) ? 0 : JSON_FORCE_OBJECT;
        if (!($json = json_encode($params, $json_flag))) {
            throw Exception('JSON encoding for params failed');
        } 
        $stream = $this->_post(http_build_query(array('params' => $json)));
        if (array_key_exists('output', $params) && 
                strtolower($params['output']) != 'json') {
            return $stream;
        }
        $result = json_decode(stream_get_contents($stream), true);
        if (!$result) {
            throw new ACIS_ResultException('server returned invalid JSON');
        }
        return $result;
    }
    
    /**
     * Execute a POST request.
     *
     * The data parameter must be a properly encoded and escaped string.
     */
    private function _post($data)
    {
        $HTTP_OK = 200;
        $HTTP_BAD = 400;
        $options = array('method' => 'POST', 'content' => $data, 
                         'ignore_errors'=>true);
        $context = stream_context_create(array('http' => $options));
        if (!($stream = @fopen($this->url, 'rb', false, $context))) {
            throw new Exception("could not open connection to {$this->url}");
        }
        list(, $code, $message) = explode(' ', $http_response_header[0], 3);
        if ($code != $HTTP_OK) {
            // This doesn't do the right thing for a "soft 404", e.g. an ISP
            // redirects to a custom error or search page for a DNS lookup
            // failure and returns a 200 (OK) code.
            if ($code == $HTTP_BAD) {
            	// If the ACIS server returns this code it also provides a
            	// helpful plain text error message.
                $message = stream_get_contents($stream);
                throw new ACIS_RequestException($message);
            }
            throw new Exception("HTTP error: {$code} {$message}");
        }
        return $stream;
    }
}
    
