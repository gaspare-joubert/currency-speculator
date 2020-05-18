<?php

/**
 * https://github.com/exchangeratesapi/exchangeratesapi
 * https://github.com/benmajor/ExchangeRatesAPI
 */

require('classes/FxSpeculator.php');
require_once('vendor/benmajor/exchange-rates-api/src/ExchangeRatesAPI.php');
require_once ('classes/ImmediateMarginFxSpeculator.php');
require_once ('classes/DelayedMarginFxSpeculator.php');
require_once ('classes/TestMarginFxSpeculator.php');
require_once ('classes/LiveMarginFxSpeculator.php');

use \BenMajor\ExchangeRatesAPI\ExchangeRatesAPI;

$exchangeRatesAPI = new ExchangeRatesAPI();
$fxSpeculator = new FxSpeculator($exchangeRatesAPI);
//$immediateMarginFxSpeculator = new ImmediateMarginFxSpeculator();
//$delayedMarginFxSpeculator = new DelayedMarginFxSpeculator();
//$testMarginFxSpeculator = new TestMarginFxSpeculator();
$liveMarginFxSpeculator = new LiveMarginFxSpeculator();