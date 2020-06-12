<?php
namespace GuzzleHttp\Exception;

require_once ('vendor/guzzlehttp/guzzle/src/Exception/GuzzleException.php');

class TransferException extends \RuntimeException implements GuzzleException
{
}
