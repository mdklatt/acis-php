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
 */
require_once 'exception.php';

define('ACIS_HTTP_OK', 200);
define('ACIS_HTTP_BAD', 400);


/**
 * An ACIS Web Services call.
 *
 * This class handles the encoding of the params object, the HTTP request and
 * error handling, and decoding of the returned result.
 */
class ACIS_WebServicesCall
{
    const _SERVER = 'http://data.rcc-acis.org';
    
    public $url;
    
    /**
     * Initialize an ACIS_WebServicesCall object.
     *
     * The call_type parameter is the type of ACIS call, e.g. "StnData".
     */
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
     */
    public function execute($params)
    {
        if (empty($params)) {
            throw new ACIS_RequestException("empty params");
        }
        if (!($json = json_encode($params))) {
            throw new RuntimeException('JSON encoding of params failed');
        } 
        $stream = $this->_post(http_build_query(array('params' => $json)));
        if (array_key_exists('output', $params) && 
                strtolower($params['output']) != 'json') {
            return $stream;
        }
        if (!($result = json_decode(stream_get_contents($stream), true))) {
            @fclose($stream);
            throw new ACIS_ResultException('server returned invalid JSON');
        }
        @fclose($stream);
        return $result;
    }
    
    /**
     * Execute a POST request.
     *
     * The data parameter must be a properly encoded and escaped string. The
     * return value is a stream resource.
     */
    private function _post($data)
    {
        $options = array('method' => 'POST', 'content' => $data, 
                         'ignore_errors'=>true);
        $context = stream_context_create(array('http' => $options));
        if (!($stream = @fopen($this->url, 'rb', false, $context))) {
            list(, $message) = error_get_last();
            throw new RuntimeException($message);
        }
        list(, $code, $message) = explode(' ', $http_response_header[0], 3);
        if ($code != ACIS_HTTP_OK) {
            // This doesn't do the right thing for a "soft 404", e.g. an ISP
            // redirects to a custom error or search page for a DNS lookup
            // failure and returns a 200 (OK) code.
            if ($code == ACIS_HTTP_BAD) {
                // If the ACIS server returns this code it also provides a
                // helpful plain text error message.
                $message = stream_get_contents($stream);
                @fclose($stream);
                throw new ACIS_RequestException($message);
            }
            @fclose($stream); 
            throw new RuntimeException("HTTP error: {$code} {$message}");
        }
        return $stream;
    }
}
    
