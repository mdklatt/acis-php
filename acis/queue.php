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
     *
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

    public $results;
    
    private $_queue;
    private $_queries;
    
    /**
     * Initialize a RequestQueue object.
     *
     */
    public function __construct()
    {
        $this->clear();
        return;
    }
    
    /**
     * Add a Request to the queue.
     *
     * The optional result paramater can be a Result class or anything that
     * accepts a query object as an argument; the query array will be 
     * converted to a result_type object.
     */
    public function add($request, $callback=null)
    {
        $params = $request->params();
        $data = http_build_query(array('params' => json_encode($params)));
        $key = $this->_queue->add($request->url(), $data);
        $this->_queries[$key] = array($request->params(), $callback);
        return;
    }
    
    /**
     * Execute all Requests in the queue.
     *
     * When execution is complete, each element of the results attribute will 
     * contain a query object or the result of a callback that accepts the
     * query as its only argument. The callback can be any callable object
     * or a class name.
     */
    public function execute()
    {
        $this->_queue->execute();
        foreach ($this->_queue->replies as $key => $reply) {
            $result = self::_result($reply);
            list($params, $callback) = $this->_queries[$key];
            $obj = array('params' => $params, 'result' => $result);
            if ($callback != null) {
                // Use the query as the argument to a function or to initialize
                // an object.
                if (is_callable($callback)) {
                    $obj = call_user_func($callback, $obj);
                }
                else {
                    // Assume that is is a class name.
                    $obj = new $callback($obj);
                }
            }
            $this->results[$key] = $obj;
        }
        $this->results = array_values($this->results);  // make keys sequential
        return;
    }

    /**
     * Clear all Requests in the queue.
     *
     */
    public function clear()
    {
        $this->_queue = new _HttpPostRequestQueue();
        $this->_queries = array();
        $this->results = array();
        return;
    }
}
 
 
/**
 * An HTTP reply. 
 */ 
class _HttpReply
{
    const _CRLF = "\r\n";
    
    public $status;
    public $content;
    
    /**
     * Initialize an HttpReply object.
     * 
     */     
    public function __construct($data)
    {
        $lines = explode(self::_CRLF, $data);
        list(, $code, $message) = explode(' ', $lines[0], 3);
        $this->status = array((int)$code, trim($message));
        for ($pos = 1; $pos < count($lines); ++$pos) {
            // Find the blank line that separates the header from the content.
            if ($lines[$pos] === '') {
                ++$pos;  // content starts on next line
                break;
            }
        }
        $this->content = implode(self::_CRLF, array_slice($lines, $pos));
        return;            
    }         
} 


/**
 * Execute HTTP POST requests asynchronously.__CRLF
 */ 
class _HttpPostRequestQueue
{
    const _CRLF = "\r\n";
    const _BLOCK_SIZE = 1024;
        
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
        $post = "POST {$url['path']} HTTP/1.0".self::_CRLF;
        $post.= 'Content-Type: application/x-www-form-urlencoded'.self::_CRLF;
        $post.= "Content-Length: {$len}".self::_CRLF;
        $post.= self::_CRLF;
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
        $len = fwrite($stream, $data, self::_BLOCK_SIZE);
        $data = substr($data, $len);
        return $len;
    }
    
    /**
     * Retrieve data from the remote connection.
     */
    private function _get($stream)
    {
        $fd = (int)$stream;
        $data = fread($stream, self::_BLOCK_SIZE);
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
