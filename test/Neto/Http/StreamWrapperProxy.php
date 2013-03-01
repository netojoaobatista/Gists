<?php
namespace Neto\Http;

class StreamWrapperProxy
{
    protected static $streamWrapper;
    protected static $cachedContext;
    public $context;

    public function __call($name, array $argv)
    {
        if (!is_callable(array(self::$streamWrapper, $name))) {
            return;
        }

        $response = call_user_func_array(array(self::$streamWrapper, $name), $argv);

        if ($this->context !== null && $this->context !== static::$cachedContext) {
            static::$cachedContext = $this->context;
        }

        return $response;
    }

    protected static function getContextOptions()
    {
        return stream_context_get_options(static::$cachedContext);
    }

    protected static function getContextParams()
    {
        return stream_context_get_params(static::$cachedContext);
    }

    public static function register($streamWrapper, $protocol = 'http')
    {
        self::$streamWrapper = $streamWrapper;

        if (in_array($protocol, stream_get_wrappers())) {
            stream_wrapper_unregister($protocol);
        }

        stream_wrapper_register($protocol, __CLASS__ , STREAM_IS_URL);
    }
}