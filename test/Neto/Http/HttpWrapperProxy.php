<?php
namespace Neto\Http;

class HttpWrapperProxy extends StreamWrapperProxy
{
    public static function getContent()
    {
        $options = static::getContextOptions();

        if (isset($options['http']) && isset($options['http']['content'])) {
            return $options['http']['content'];
        }

        return null;
    }

    public static function getRequestHeaders()
    {
        $options = static::getContextOptions();
        $headers = array();

        if (isset($options['http']) && isset($options['http']['header'])) {
            $requestHeaders = preg_split('/(\r\n|\r|\n)/', $options['http']['header']);

            foreach ($requestHeaders as $requestHeader) {
                $header = preg_split('/:\s?/', $requestHeader);

                $headers[$header[0]] = $header[1];
            }
        }

        return $headers;
    }

    public static function getRequestMethod()
    {
        $options = static::getContextOptions();

        if (isset($options['http']) && isset($options['http']['method'])) {
            return $options['http']['method'];
        }
    }
}