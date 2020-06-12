<?php
namespace GuzzleHttp\Exception;

require_once ('vendor/guzzlehttp/guzzle/src/Exception/BadResponseException.php');

/**
 * Exception when a client error is encountered (4xx codes)
 */
class ClientException extends BadResponseException
{
}
