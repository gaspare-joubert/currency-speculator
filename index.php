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
require_once ('classes/FindTrendAppreciationFxSpeculator.php');
require_once ('classes/FindTrendProfitPointFxSpeculator.php');

use \BenMajor\ExchangeRatesAPI\ExchangeRatesAPI;

$exchangeRatesAPI = new ExchangeRatesAPI();
$fxSpeculator = new FxSpeculator($exchangeRatesAPI);
//$immediateMarginFxSpeculator = new ImmediateMarginFxSpeculator();
//$delayedMarginFxSpeculator = new DelayedMarginFxSpeculator();
//$testMarginFxSpeculator = new TestMarginFxSpeculator();
//$liveMarginFxSpeculator = new LiveMarginFxSpeculator();
//$findTrendAppreciationFxSpeculator = new FindTrendAppreciationFxSpeculator();
$findTrendProfitPointFxSpeculator = new FindTrendProfitPointFxSpeculator();