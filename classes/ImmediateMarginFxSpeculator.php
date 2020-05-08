<?php

/**
 * Class ImmediateMarginFxSpeculator
 *
 * Calculate Immediate Margin
 * Using maximum 5 iterations
 *
 * Fetch rates
 * baseCurrency = GBP
 *
 * Foreach Currency
 * Convert to Currency
 * baseCurrency = Currency
 *
 * For 3 iterations
 * Find baseCurrency's weakest Currency
 * Convert to Currency
 * baseCurrency = Currency
 *
 * Convert Currency to GBP
 * Calculate Profit/Loss
 */
class ImmediateMarginFxSpeculator Extends FxSpeculator
{
    private $baseCurrencyOriginal;
    private $currencies = array();

    /**
     * ImmediateMarginFxSpeculator constructor.
     *
     * Set $this->exchangeRatesAPI = $exchangeRatesAPI
     * Set baseCurrency = 'GBP'
     */
    public function __construct()
    {
        parent::__construct($GLOBALS["fxSpeculator"]->exchangeRatesAPI);
        $this->baseCurrencyOriginal = 'GBP';
        $this->main();
    }

    private function main()
    {
        /**
         * Fetch rates
         * baseCurrency = GBP
         */
        $this->fetchRates($this->baseCurrencyOriginal);
        $this->currencies = $this->rates->getRates();

        /**
         * Foreach Currency
         * Convert to Currency
         * baseCurrency = Currency
         */
        foreach ($this->currencies as $key=>$val)
        {
            $test = '';
        }

        print_r($this->currencies);
    }
}