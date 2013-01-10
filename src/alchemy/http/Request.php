<?php
/**
 * Copyright (C) 2012 Dawid Kraczkowski
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR
 * A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace alchemy\http;
class RequestException extends \Exception {}
class Request 
{
    /**
     * Gets global request performed to application
     * @return Request
     */
    public static function getGlobal()
    {
        if (!(self::$globalRequest instanceof Request)) {

            //create headers
            $headers = array(
                'Host'              => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null,
                'Connection'        => isset($_SERVER['HTTP_CONNECTION']) ? $_SERVER['HTTP_CONNECTION'] : null,
                'User-Agent'        => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null,
                'Cache-Control'     => isset($_SERVER['HTTP_CACHE_CONTROL']) ? $_SERVER['HTTP_CACHE_CONTROL'] : null,
                'Accept-Encoding'   => isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null,
                'Accept-Language'   => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null,
                'Accept-Charset'    => isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : null
            );

            self::$globalRequest = new self($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_POST, new Headers($headers));
            //is XHR
            self::$globalRequest->isXHR(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
            //is secure
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
                self::$globalRequest->isSecure(true);
            }

        }
        return self::$globalRequest;
    }

    /**
     * Constructor
     *
     * @param string $url request url or uri
     * @param string $method request method (GET, POST, DELETE OR PUT)
     * @param array $parameters request post data
     * @param Headers $headers requests headers @see Header
     */
    public function __construct($url, $method = self::METHOD_GET, $data = array(), Headers $headers = null)
    {
        $url = parse_url($url);

        $this->uri = isset($url['path']) ? $url['path'] : '/';
        if (isset($url['query'])) {
            parse_str($url['query'], $this->query);
        }
        $this->data = $data;
        if (isset($url['scheme'])) {
            $this->scheme = $url['scheme'];
        }
        if (isset($url['host'])) {
            $this->host = $url['host'];
        }

        $this->method = $method;
        $this->headers = $headers;
    }

    /**
     * Checks whatever request's method is POST
     * @return bool true if request's method is POST
     */
    public function isPost()
    {
        return $this->getMethod() == self::METHOD_POST;
    }

    /**
     * Checks whatever request's method is GET
     * @return bool true is request's method is GET
     */
    public function isGet()
    {
        return $this->getMethod() == self::METHOD_GET;
    }

    /**
     * Checks whatever request's method is DELETE
     * @return bool true is request's method is DELETE
     */
    public function isDelete()
    {
        return $this->getMethod() == self::METHOD_DELETE;
    }

    /**
     * Checks whatever request's method is PUT
     * @return bool true is request's method is PUT
     */
    public function isPut()
    {
        return $this->getMethod() == self::METHOD_PUT;
    }

    /**
     * Checks whatever request was made as XHR
     * @return bool
     */
    public function isXHR($set = null)
    {
        if ($set !== null) {
            $this->isXHR = $set;
        }
        return $this->isXHR;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    /**
     * Sends a request
     *
     * @param int $timeout timeout in ms
     * @return Response
     * @throws RequestException
     */
    public function send($timeout = null)
    {
        //set curl options
        $url = $this->scheme . '://' . $this->host . $this->uri . (empty($this->query) ? '' : '?' . http_build_query($this->query));
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER  => true
        );
        if ($timeout) {
            $options[CURLOPT_TIMEOUT_MS] = $timeout;
        }
        if (!empty($this->data)) {
            $options[CURLOPT_POST]          = 1;
            $options[CURLOPT_POSTFIELDS]    = $this->data;
        }

        if ($this->method == self::METHOD_PUT) {
            $options[CURLOPT_PUT]               = 1;
            $options[CURLOPT_BINARYTRANSFER]    = 1;
        }

        if ($this->caFile) {
            $this->verifyPeer = true;
            $options[CURLOPT_CAINFO] = $this->caFile;
        }
        $options[CURLOPT_SSL_VERIFYPEER] = $this->verifyPeer;
        $options[CURLOPT_SSL_VERIFYHOST] = $this->verifyPeer;

        if ($this->headers) {
            $options[CURLOPT_HTTPHEADER] = $this->headers->toArray();
        }

        //perform curl request
        $handler = curl_init($url);
        curl_setopt_array($handler, $options);
        $result = curl_exec($handler);
        $info = curl_getinfo($handler);
        $errorNo = curl_errno($handler);
        $errorMessage = curl_error($handler);
        curl_close($handler);

        if ($errorNo !== 0) {
            throw new RequestException($errorMessage, $errorNo);
        }
        $header = explode("\n", substr($result, 0, $info['header_size']));
        $body = substr($result, $info['header_size']);

        //http version
        $version = substr($header[0],5,3);
        array_shift($header);

        $headers = new Headers();

        foreach ($header as $h) {
            $pos = strpos($h, ':');
            if (!$pos) {
                continue;
            }
            $headers->set(substr($h, 0, $pos), substr($h, $pos + 2));
        }


        $response = new Response($body, $info['http_code']);
        $response->setVersion($version);
        return $response;
    }

    public function setCookieJar($file)
    {
        $this->cookieJar = $file;
    }

    /**
     * Sets request's data
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Returns request's data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function setVerifyPeer($verify = true)
    {
        $this->verifyPeer = $verify;
    }

    public function setCAFile($filename)
    {
        if (!is_readable($filename)) {
            throw new RequestException(sprintf('Cert info file `%s` is not readable', $filename));
        }
        $this->caFile = $filename;
    }

    public function isSecure($set = null)
    {
        if ($set !== null) {
            if ($set) {
                $this->scheme = 'https';
            } else {
                $this->scheme = 'http';
            }
        }
        return $this->scheme == 'https';
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function setScheme($scheme = 'http')
    {
        $this->scheme = $scheme;
    }
    public function getMethod()
    {
        return $this->method;
    }

    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers;
    }

    public function getHeader($header)
    {
        return $this->headers[$header];
    }

    public function getAllHeaders()
    {
        return $this->headers;
    }

    public function getURI()
    {
        return $this->uri;
    }

    /**
     * Query
     * @var array
     */
    protected $query = array();

    /**
     * @var string
     */
    protected $uri = '/';

    /**
     * @var string
     */
    protected $host;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var string
     */
    protected $method;

    /**
     * @var bool
     */
    protected $isXHR = false;

    /**
     * @var string
     */
    protected $scheme = 'http';

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @var bool
     */
    protected $verifyPeer = false;

    /**
     * Filename where cookies are preserved
     *
     * @var string
     */
    protected $cookieJar;

    protected $caFile;

    /**
     * @var Request
     */
    private static $globalRequest;

    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_PUT    = 'PUT';
    const METHOD_DELETE = 'DELETE';

}