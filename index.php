<?php

/**
 * https://github.com/exchangeratesapi/exchangeratesapi
 * https://github.com/benmajor/ExchangeRatesAPI
 */

require('classes/FxSpeculator.php');
require_once('vendor/benmajor/exchange-rates-api/src/ExchangeRatesAPI.php');
require_once ('classes/ImmediateMarginFxSpeculator.php');

use \BenMajor\ExchangeRatesAPI\ExchangeRatesAPI;

$exchangeRatesAPI = new ExchangeRatesAPI();
$fxSpeculator = new FxSpeculator($exchangeRatesAPI);
$immediateMarginFxSpeculator = new ImmediateMarginFxSpeculator();