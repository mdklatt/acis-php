<?php
/**
 * Parallel execution of multiple ACIS requests.
 *
 * USE AT YOUR OWN RISK.
 * In situations where server-side processing is the bottleneck, application
 * performance can be enhanced by executing requests in parallel on the ACIS
 * server.
 *
 * This is _very_ alpha and cannot handle things like server redirects (but 
 * ACIS isn't doing this...yet). An error with one request will take the whole
 * queue down. The interface should not be considered stable. 
 */

 /**
  * Manage parallel Requests.
  */
class ACIS_RequestQueue
{
    /**
     * Convert a POST reply to a JSON result object.
     */
    static private function _result($reply)
    {
        list($code, $message) = $reply->status;
        if ($code != ACIS_HTTP_OK) {
            // This doesn't do the right thing for a "soft 404", e.g. an 
            // ISP redirects to a custom error or search page for a DNS
            // lookup failure and returns a 200 (OK) code.
            if ($code == ACIS_HTTP_BAD) {
                // If the ACIS server returns this code it also provides a
                // helpful plain text error message.
                throw new ACIS_RequestException($reply->content);
            }
            throw new RuntimeException("HTTP error: {$code} {$message}");
        }
        return json_decode($reply->content, true);
    }

    public $results = array();
    
    private $_queue;
    private $_queries = array();
    
    /**
     * Initialize a RequestQueue object.
     */
    public function __construct()
    {
        $this->_queue = new _HttpPostRequestQueue();
    }
    
    /**
     * Add a Request to the queue.
     *
     * The optional result paramater can be a Result class or anything that
     * accepts a query object as an argument; the query array will be 
     * converted to a result_type object.
     */
    public function add($request, $result_type=null)
    {
        $params = $request->params();
        $data = http_build_query(array('params' => json_encode($params)));
        $key = $this->_queue->add($request->url(), $data);
        $this->_queries[$key] = array($request->params(), $result_type);
        return;
    }
    
    /**
     * Execute all Requests in the queue.
     *
     * When execution is complete, each element of the results attribute will 
     * contain a query object or the optional result type specified for that 
     * request.     
     */
    public function execute()
    {
        $this->_queue->execute();
        foreach ($this->_queue->replies as $key => $reply) {
            $result = self::_result($reply);
            list($params, $result_type) = $this->_queries[$key];
            $query = array('params' => $params, 'result' => $result);
            if ($result_type == null) {
                $this->results[$key] = $query;
            }
            else {
                $this->results[$key] = new $result_type($query);
            }
        }
        $this->results = array_values($this->results);  // make keys sequential
        return;
    }
}
 
 
/**
 * An HTTP reply. 
 */ 
class _HttpReply
{
    const CRLF = "\r\n";
    
    public $status;
    public $content;
    
    /**
     * Initialize an HttpReply object. 
     */     
    public function __construct($data)
    {
        $lines = explode(self::CRLF, $data);
        list(, $code, $message) = explode(' ', $lines[0], 3);
        $this->status = array((int)$code, trim($message));
        for ($pos = 1; $pos < count($lines); ++$pos) {
            // Find the blank line that separates the header from the content.
            if ($lines[$pos] === '') {
                ++$pos;  // content starts on next line
                break;
            }
        }
        $this->content = implode(self::CRLF, array_slice($lines, $pos));
        return;            
    }         
} 


/**
 * Execute HTTP POST requests asynchronously.
 */ 
class _HttpPostRequestQueue
{
    const CRLF = "\r\n";
    const BLOCK_SIZE = 1024;
        
    public $replies = array();
    
    private $_streams = array();
    private $_putbuf = array();
    private $_getbuf = array();
    
    /**
     * Add a request to the queue.
     *
     * The request is made to 'url' using POST content 'data'.
     */
    public function add($url, $data)
    {
        $url = parse_url($url);
        $len = strlen($data);
        $post = "POST {$url['path']} HTTP/1.0".self::CRLF;
        $post.= 'Content-Type: application/x-www-form-urlencoded'.self::CRLF;
        $post.= "Content-Length: {$len}".self::CRLF;
        $post.= self::CRLF;
        $post.= $data;
        $fd = $this->_connect($url['host']);
        $this->_putbuf[$fd] = $post;
        $this->_getbuf[$fd] = '';
        $this->replies[$fd] = null;  // make sure replies will be in order
        return $fd;
    }
    
    /**
     * Execute all requests in the queue.
     */   
    public function execute()
    {
        // Continue until the queue is empty. As an EOF is detected on each
        // connection it is removed from the queue.
        while (count($this->_streams) > 0)
        {
            $readers = $writers = $errors = $this->_streams;
            stream_select($readers, $writers, $errors, 0);
            foreach ($writers as $stream) {
                $this->_put($stream);
            }
            foreach ($readers as $stream) {
                $this->_get($stream);
                if (feof($stream)) {
                    unset($this->_streams[(int)$stream]);
                    $this->_eof($stream);
                }
            }
        }
        return; 
    }
    
    /**
     * Create a socket connection to a remote host.
     */   
    private function _connect($host)
    {
        $remote = "tcp://{$host}:80";
        $stream = @stream_socket_client($remote, $errnum, $errstr);
        if (!$stream) {
            throw new RuntimeException($errstr);            
        }
        stream_set_blocking($stream, 0);
        $fd = (int)$stream;
        $this->_streams[$fd] = $stream;
        $this->results[$fd] = null;  // make sure results are stored in order
        return $fd;
    }
    
    /**
     * Send data to the remote connection.
     */   
    private function _put($stream)
    {
        $data = &$this->_putbuf[(int)$stream];
        if (strlen($data) == 0) {
            return 0;
        }
        $len = fwrite($stream, $data, self::BLOCK_SIZE);
        $data = substr($data, $len);
        return $len;
    }
    
    /**
     * Retrieve data from the remote connection.
     */
    private function _get($stream)
    {
        $fd = (int)$stream;
        $data = fread($stream, self::BLOCK_SIZE);
        if (($len = strlen($data)) > 0) {
            $this->_getbuf[(int)$stream].= $data;
        }
        return $len;
    }
    
    /**
     * Close the remote connection and store the reply 
     */
     private function _eof(&$stream)
     {
         $fd = (int)$stream;
         @fclose($stream);
         $this->replies[$fd] = new _HttpReply($this->_getbuf[$fd]);
         return;
     }
}
