<?php
namespace Neto\Http;

class HttpRequest
{
    const DEFAULT_PORT = 80;
    const DEFAULT_TIMEOUT = 60;

    /**
     * RFC 2616 sec 9.9
     *
     * @var string
     */
    const CONNECT = 'CONNECT';

    /**
     * RFC 2616 sec 9.7
     *
     * @var string
     */
    const DELETE = 'DELETE';

    /**
     * RFC 2616 sec 9.3
     *
     * @var string
     */
    const GET = 'GET';

    /**
     * RFC 2616 sec 9.4
     *
     * @var string
     */
    const HEAD = 'HEAD';

    /**
     * RFC 2616 sec 9.2
     *
     * @var string
     */
    const OPTIONS = 'OPTIONS';

    /**
     * RFC 2616 sec 9.5
     *
     * @var string
     */
    const POST = 'POST';

    /**
     * RFC 2616 sec 9.6
     *
     * @var string
     */
    const PUT = 'PUT';

    /**
     * RFC 2616 sec 9.8
     *
     * @var string
     */
    const TRACE = 'TRACE';

    private $hostname;
    private $initialized = false;
    private $port;
    private $requestHeader = array();
    private $requestParam = array();
    private $timeout;

    public function addHeader($name, $value, $override = true)
    {
        if (is_scalar($name) && is_scalar($value)) {
            $key = strtolower($name);

            if ($override === true || !isset($this->requestHeader[$key])) {
                $this->requestHeader[$key] = array('name' => $name,
                    'value' => $value
                );

                return true;
            }

            return false;
        }

        throw new \InvalidArgumentException('Name and value MUST be scalar');
    }

    private function buildHeaders()
    {
        if (count($this->requestHeader) > 0) {
            $header = '';

            foreach ($this->requestHeader as $requestHeader) {
                if (!empty($header)) {
                    $header .= "\r\n";
                }

                $header .= $requestHeader['name'] . ': ' . $requestHeader['value'];
            }

            return $header;
        }

        return null;
    }

    private function buildQuery()
    {
        if (count($this->requestParam) > 0) {
            $query = '';

            foreach ($this->requestParam as $name => $value) {
                if (!empty($query)) {
                    $query .= '&';
                }

                $query .= urlencode($name);

                if ($value !== null) {
                    $query .= '=' . urlencode($value);
                }
            }

            return $query;
        }

        return null;
    }

    public function execute($path = '/', $method = HttpRequest::GET)
    {
        $options = array(
            'http' => array(
                'method' => $method,
                'ignore_errors' => true,
                'timeout' => $this->timeout
            )
        );

        $header = $this->buildHeaders();

        if (!empty($header)) {
            $options['http']['header'] = $header;
        }

        $url = 'http://' . $this->getHostname();

        if ($this->port !== HttpRequest::DEFAULT_PORT) {
            $url .= ':' . $this->port;
        }

        $url .= $path;

        $query = $this->buildQuery();

        if (!empty($query)) {
            if ($method == HttpRequest::GET || $method == HttpRequest::HEAD) {
                $url .= '?' . $query;
            } else {
                $options['http']['content'] = $query;
            }
        }

        return file_get_contents($url, false, stream_context_create($options));
    }

    public function getHeader($name)
    {
        $key = strtolower($name);

        if (isset($this->requestHeader[$key])) {
            return $this->requestHeader[$key]['value'];
        }

        return null;
    }

    public function getHostname()
    {
        if (!$this->initialized) {
            throw new \BadMethodCallException();
        }

        return $this->hostname;
    }

    public function getParam($name)
    {
        if (isset($this->requestParam[$name])) {
            return $this->requestParam[$name];
        }

        return null;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function initialize($hostname,
                               $port = HttpRequest::DEFAULT_PORT,
                               $timeout = HttpRequest::DEFAULT_TIMEOUT)
    {
        if (!filter_var($hostname, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid hostname');
        }

        $url = parse_url($hostname);

        if (func_num_args() == 1 && isset($url['port'])) {
            $port = $url['port'];
        }

        if (!is_int($port) || !is_int($timeout)) {
            throw new \InvalidArgumentException(
                'Port number and timeout must be integer values');
        }

        if (isset($url['scheme']) && $url['scheme'] !== 'http') {
            throw new \UnexpectedValueException(
                'Unexpected scheme. Only HTTP requests permitted.');
        }

        $this->hostname = $url['host'];
        $this->port = (int) $port;
        $this->timeout = $timeout;
        $this->initialized = true;
    }

    function setParam($name, $value = null)
    {
        if (is_scalar($name) && (is_scalar($value) || is_null($value))) {
            $this->requestParam[$name] = $value;
        } else {
            throw new \InvalidArgumentException('Name and value MUST be scalar');
        }
    }
}