<?php

require_once('vendor/benmajor/exchange-rates-api/src/ExchangeRatesAPI.php');

use BenMajor\ExchangeRatesAPI\Exception;
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
    protected $qtyOriginal; // The number of the original currency units to exchange

    /**
     * FxSpeculator constructor
     *
     * Set dateToday
     * Set dateYesterday = dateToday - 1day
     */
    public function __construct(\BenMajor\ExchangeRatesAPI\ExchangeRatesAPI $exchangeRatesAPI)
    {
        $date = new DateTime();
        //$this->dateToday = $date->format('Y-m-d');
        $this->dateToday = '2020-05-06';
        //$this->dateYesterday = $date->sub(new DateInterval('P1D'))->format('Y-m-d');
        $this->dateYesterday = $this->dateToday;
        $this->exchangeRatesAPI = $exchangeRatesAPI;
        $this->qtyOriginal = '1000';
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
        $this->rates = $lookup->addRates(['EUR', 'JPY', 'BGN', 'CZK', 'DKK'])->setBaseCurrency($baseCurrency)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch();
    }

    /**
     * @param $baseCurrency
     * @param $removeRate
     * @throws Exception
     *
     * Fetch rates, excluding baseCurrency
     * * based on the baseCurrency
     * * using addDateFrom
     * * using addDateTo
     */
    protected function fetchRatesWithRemovedRate($baseCurrency, $removeRate)
    {
        $lookup = $this->exchangeRatesAPI;

        //$this->rates = $lookup->setBaseCurrency($baseCurrency)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch();
        //$this->exchangeRatesAPI->rates = '';
        //$this->rates = $lookup->addRates(['EUR', 'JPY', 'BGN', 'CZK', 'DKK'])->setBaseCurrency($baseCurrency)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch();
        $this->rates = $lookup->removeRate($removeRate)->setBaseCurrency($baseCurrency)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch();
    }

    /**
     * @param string $to
     * @param float $amount
     * @param float $rate
     * @param int $rounding
     * @return false|float
     * @throws Exception
     *
     *
     */
    protected function convertBaseToCurrency(string $to, float $amount, float $rate, $rounding = 2)
    {
        $currencyTo = $this->exchangeRatesAPI->sanitizeCurrencyCode($to);

        # Check it's an allowed currency:
        $this->exchangeRatesAPI->verifyCurrencyCode($to);

        if( !is_numeric($amount) )
        {
            throw new Exception( $this->_errors['format.invalid_amount'] );
        }

        if( ! is_numeric($rounding) )
        {
            throw new Exception( $this->_errors['format.invalid_rounding'] );
        }

        return round(
            ($amount * $rate),
            $rounding
        );
    }
}