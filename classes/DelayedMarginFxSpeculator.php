<?php

/**
 * Class DelayedMarginFxSpeculator
 *
 * Calculate Delayed Margin
 * Using maximum 1 iteration
 *
 * Fetch rates on day1
 * baseCurrency = $baseCurrencyOriginal
 *
 * Fetch rates on day1 + 7days
 * baseCurrency = $baseCurrencyOriginal
 *
 * Compare rates for day1 and day7
 * Find rate with 10% depreciation
 * Convert the original qty of Currency units to Currency
 * baseCurrency = Currency
 * Fetch rates
 *
 * Fetch rates on day7 + 7days
 * Find rate with 10% depreciation
 * Convert the original qty of Currency units to Currency
 * baseCurrency = Currency
 * Fetch rates
 *
 * Convert Currency to GBP
 * Calculate Profit/Loss
 */
class DelayedMarginFxSpeculator Extends FxSpeculator
{
    private $currencies = array();
    private $currencies01 = array();
    private $currencies02 = array();
    private $currencies03 = array();

    /**
     * DelayedMarginFxSpeculator constructor.
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
         * Fetch rates for day1
         * baseCurrency = baseCurrencyOriginal
         */
        try {
            $this->dateToday = '2020-03-02';
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->baseCurrencyOriginal);
        } catch (\Exception $ex) {
        }

        if (empty($this->rates)) {
            return;
        }

        $this->currencies['day1'][$this->baseCurrencyOriginal] = $this->rates;

        if (empty($this->currencies)) {
            return;
        }

        /////////////////////////////////////////////

        /**
         * Fetch rates for day1 + 7days
         * baseCurrency = baseCurrencyOriginal
         */
        try {
            $this->dateToday = '2020-03-09';
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->baseCurrencyOriginal);
        } catch (\Exception $ex) {
        }

        if (empty($this->rates)) {
            return;
        }

        $this->currencies['day1+7'][$this->baseCurrencyOriginal] = $this->rates;

        if (empty($this->currencies)) {
            return;
        }

        /////////////////////////////////////////////

        /**
         * Compare rates for day1 and day7
         * Find rate with 10% depreciation
         * Convert the original qty of Currency units to Currency
         * baseCurrency = Currency
         * Fetch rates
         */

        foreach ($this->currencies['day1'][$this->baseCurrencyOriginal]['2020-03-02'] as $key => $val) {
            $val1 = $this->currencies['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'][$key];
            $appreciation = '';

            if($val1 > $val) {
                $appreciation = round(((($val1 / $val) * 100) - 100) , 2);
                $this->currencies01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'][$key] = $appreciation;
            }
        }

        if (empty($this->currencies01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'])) {
            return;
        }

        if(arsort($this->currencies01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'])) {
            $val1 = reset($this->currencies01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09']);
            $key1 = key($this->currencies01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09']);

            $this->ratesToRemove = [$this->baseCurrencyOriginal, $key1];

            try {
                $qtyCurrency = $this->convertBaseToCurrency($key1, $this->qtyOriginal, $val1);
            } catch (\Exception $ex) {
            } finally {
                if (!isset($ex) && !empty($qtyCurrency)) {
                    $this->fetchRates($key1);

                    if (empty($this->rates)) {
                        return;
                    }

                    $this->currencies['day1+7'][$key1]['2020-03-09'] = $this->rates;
                    $this->currencies02['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'][$key1] = $qtyCurrency;
                }
            }
        }

        if (empty($this->currencies['day1+7'][$key1]['2020-03-09'])) {
            return;
        }

        /////////////////////////////////////////////

        /**
         * Fetch rates on day7 + 7days
         * Find rate with 10% depreciation
         * Convert the original qty of Currency units to Currency
         * baseCurrency = Currency
         * Fetch rates
         */
        try {
            $this->dateToday = '2020-03-16';
            $this->dateYesterday = $this->dateToday;
            $keyNow = key($this->currencies02['day1+7'][$this->baseCurrencyOriginal]['2020-03-09']);
            $this->ratesToRemove = [$keyNow];
            $this->fetchRates($keyNow);
        } catch (\Exception $ex) {
        }

        if (empty($this->rates)) {
            return;
        }

        $this->currencies['day7+7'][$keyNow] = $this->rates;

        if (empty($this->currencies['day7+7'][$keyNow])) {
            return;
        }

        foreach ($this->currencies['day1+7'][$keyNow]['2020-03-09']['2020-03-09'] as $key => $val) {
            $val1 = $this->currencies['day7+7'][$keyNow]['2020-03-16'][$key];
            $appreciation = '';

            if($val1 > $val) {
                $appreciation = round(((($val1 / $val) * 100) - 100) , 2);
                $this->currencies02['day7+7'][$keyNow]['2020-03-16'][$key] = $appreciation;
            }
        }

        if (empty($this->currencies02['day7+7'][$keyNow]['2020-03-16'])) {
            return;
        }

        if(arsort($this->currencies02['day7+7'][$keyNow]['2020-03-16'])) {
            $val1 = reset($this->currencies02['day7+7'][$keyNow]['2020-03-16']);
            $key1 = key($this->currencies02['day7+7'][$keyNow]['2020-03-16']);

            $this->ratesToRemove = [$key1];
            $qty = $this->currencies02["day1+7"][$this->baseCurrencyOriginal]["2020-03-09"][$keyNow];

            try {
                $qtyCurrency = $this->convertBaseToCurrency($key1, $qty, $val1);
            } catch (\Exception $ex) {
            } finally {
                if (!isset($ex) && !empty($qtyCurrency)) {
                    $this->fetchRates($key1);

                    if (empty($this->rates)) {
                        return;
                    }

                    // Convert currency to original base currency here.

                }
            }
        }

        /////////////////////////////////////////////
    }
}