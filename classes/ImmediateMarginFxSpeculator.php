<?php

/**
 * Class ImmediateMarginFxSpeculator
 *
 * Calculate Immediate Margin
 * Using maximum 5 iterations
 *
 * Fetch rates
 * baseCurrency = $baseCurrencyOriginal
 *
 * Foreach Currency
 * Convert the original qty of Currency units to Currency *
 *
 * For 3 iterations
 * baseCurrency = Currency
 * Find baseCurrency's highest conversion Currency
 * Convert to Currency
 *
 * Convert Currency to GBP
 * Calculate Profit/Loss
 */
class ImmediateMarginFxSpeculator Extends FxSpeculator
{
    private $baseCurrencyOriginal;
    private $currencies = array();
    private $currencies01 = array();
    private $currencies02 = array();

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
         * baseCurrency = baseCurrencyOriginal
         */
        try {
            $this->fetchRates($this->baseCurrencyOriginal);
        } catch (\Exception $ex) {
        }

        if (empty($this->rates)) {
            return;
        }

        $this->currencies = $this->rates->getRates();

        /////////////////////////////////////////////

        if (empty($this->currencies)) {
            return;
        }

        /**
         * Foreach Currency
         * Convert the original qty of Currency units to Currency
         */
        foreach ($this->currencies[$this->dateToday] as $key => $val) {
            try {
                $qtyCurrency = $this->convertBaseToCurrency($key, $this->qtyOriginal, $val);
            } catch (\Exception $ex) {
            } finally {
                if (!isset($ex) && !empty($qtyCurrency)) {
                    $this->currencies01[$this->dateToday]['currencies01'][$key] = $qtyCurrency;
                }
            }
        }

        if (empty($this->currencies01[$this->dateToday]['currencies01'])) {
            return;
        }

        /////////////////////////////////////////////

        /**
         * For 3 iterations
         * baseCurrency = Currency
         * Find baseCurrency's highest conversion Currency
         * Convert to Currency
         */
        foreach ($this->currencies[$this->dateToday] as $key => $val) {
            $baseCurrency = $key;

            try {
                //$this->fetchRates($baseCurrency);
                $this->fetchRatesWithRemovedRate($baseCurrency, $key); // For Testing Only!!
            } catch (\Exception $ex) {
            }

            $this->currencies02[$this->dateToday]['currencies02'][$key] = $this->rates->getRates();
            $this->rates = $this->exchangeRatesAPI->addRate($key)->setBaseCurrency($this->baseCurrencyOriginal)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch(); // For Testing Only!!
        }

        if (empty($this->currencies02[$this->dateToday]['currencies02'])) {
            return;
        }

        foreach ($this->currencies02[$this->dateToday]['currencies02'] as $key => $val) {

        }

        print_r([$this->currencies]);
        echo '<br/>';
        echo '<br/>';
        echo ('-----------------------');
        echo '<br/>';
        echo '<br/>';
        print_r([$this->currencies01]);
        echo '<br/>';
        echo '<br/>';
        echo ('-----------------------');
        echo '<br/>';
        echo '<br/>';
        print_r([$this->currencies02]);
        echo '<br/>';
        echo '<br/>';
        echo ('-----------------------');
    }
}