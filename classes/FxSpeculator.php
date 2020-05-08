<?php

require_once('vendor/benmajor/exchange-rates-api/src/ExchangeRatesAPI.php');

use \BenMajor\ExchangeRatesAPI\ExchangeRatesAPI;

/**
 * Class FxSpeculator
 *
 * Base class
 */
class FxSpeculator
{
    protected $dateToday;
    protected $dateYesterday;
    protected $exchangeRatesAPI;
    protected $rates;

    /**
     * FxSpeculator constructor
     *
     * Set dateToday
     * Set dateYesterday = dateToday - 1day
     */
    public function __construct(\BenMajor\ExchangeRatesAPI\ExchangeRatesAPI $exchangeRatesAPI)
    {
        $date = new DateTime();
        $this->dateToday = $date->format('Y-m-d');
        $this->dateYesterday = $date->sub(new DateInterval('P1D'))->format('Y-m-d');
        $this->exchangeRatesAPI = $exchangeRatesAPI;
    }

    /**
     * @param string $baseCurrency
     * @throws \BenMajor\ExchangeRatesAPI\Exception
     *
     * Set $lookup = $this->exchangeRatesAPI
     *
     * Fetch all rates
     * * based on the baseCurrency
     * * using addDateFrom
     * * using addDateTo
     */
    protected function fetchRates($baseCurrency)
    {
        $lookup = $this->exchangeRatesAPI;
        //$this->rates = $lookup->setBaseCurrency($baseCurrency)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch();
        $this->rates = $lookup->addRates(['CAD', 'HKD', 'ISK'])->setBaseCurrency($baseCurrency)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch();
    }
}