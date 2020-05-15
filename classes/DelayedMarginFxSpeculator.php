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
 * On day14 + 7days
 * Convert Currency to GBP
 * Calculate Profit/Loss
 */
class DelayedMarginFxSpeculator Extends FxSpeculator
{
    private $fxrates = array();
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

        $this->fxrates['day1'][$this->baseCurrencyOriginal] = $this->rates;

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

        $this->fxrates['day1+7'][$this->baseCurrencyOriginal] = $this->rates;

        /////////////////////////////////////////////

        /**
         * Compare rates for day1 and day7
         * Find rate with 10% depreciation
         * Convert the original qty of Currency units to Currency
         * baseCurrency = Currency
         * Fetch rates
         */

        foreach ($this->fxrates['day1'][$this->baseCurrencyOriginal]['2020-03-02'] as $key => $val) {
            $val1 = $this->fxrates['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'][$key];
            $appreciation = '';

            if($val1 > $val) {
                $appreciation = round(((($val1 / $val) * 100) - 100) , 2);
                $this->appreciation01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'][$key] = $appreciation;
            }
        }

        if(arsort($this->appreciation01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'])) {
            reset($this->appreciation01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09']); // error!! this returns the appreciation!! must return the rate!!
            $key1 = key($this->appreciation01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09']);
            $val1 = $this->addCurrencyConversionCost($this->fxrates['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'][$key1]);

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

                    $this->fxrates['day1+7'][$key1]['2020-03-09'] = $this->rates;
                    $this->currencies01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'][$key1] = $qtyCurrency;
                }
            }
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
            $currencyDay1_7 = key($this->currencies01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09']);
            $this->ratesToRemove = [$currencyDay1_7];
            $this->fetchRates($currencyDay1_7);
        } catch (\Exception $ex) {
        }

        if (empty($this->rates)) {
            return;
        }

        $this->fxrates['day7+7'][$currencyDay1_7] = $this->rates;

        if (empty($this->fxrates['day7+7'][$currencyDay1_7])) {
            return;
        }

        foreach ($this->fxrates['day1+7'][$currencyDay1_7]['2020-03-09']['2020-03-09'] as $key => $val) {
            $val1 = $this->fxrates['day7+7'][$currencyDay1_7]['2020-03-16'][$key];
            $appreciation = '';

            if($val1 > $val) {
                $appreciation = round(((($val1 / $val) * 100) - 100) , 2);
                $this->appreciation02['day7+7'][$currencyDay1_7]['2020-03-16'][$key] = $appreciation;
            }
        }

        if(arsort($this->appreciation02['day7+7'][$currencyDay1_7]['2020-03-16'])) {
            reset($this->appreciation02['day7+7'][$currencyDay1_7]['2020-03-16']);
            $key1 = key($this->appreciation02['day7+7'][$currencyDay1_7]['2020-03-16']);
            $val1 = $this->addCurrencyConversionCost($this->fxrates['day7+7'][$currencyDay1_7]['2020-03-16'][$key1]);

            $this->ratesToRemove = [$key1];
            $qty = $this->currencies01['day1+7'][$this->baseCurrencyOriginal]['2020-03-09'][$currencyDay1_7]; // Confirm the correct currency qty is being used!!

            try {
                $qtyCurrency = $this->convertBaseToCurrency($key1, $qty, $val1);
            } catch (\Exception $ex) {
            } finally {
                if (!isset($ex) && !empty($qtyCurrency)) {
                    $this->fetchRates($key1);

                    if (empty($this->rates)) {
                        return;
                    }

                    $this->fxrates['day7+7'][$key1]['2020-03-16'] = $this->rates;
                    $this->currencies02['day7+7'][$this->baseCurrencyOriginal]['2020-03-16'][$currencyDay1_7][$key1] = $qtyCurrency;
                }
            }
        }

        /////////////////////////////////////////////
        /**
         * On day14 + 7days
         * Convert Currency to GBP
         * Calculate Profit/Loss
         */

        try {
            $currencyDay7_7 = key($this->currencies02['day7+7'][$this->baseCurrencyOriginal]['2020-03-16'][$currencyDay1_7]);
            $qty = $this->currencies02['day7+7'][$this->baseCurrencyOriginal]['2020-03-16'][$currencyDay1_7][$currencyDay7_7];

            $this->dateToday = '2020-03-23';
            $this->dateYesterday = $this->dateToday;
            $this->ratesToRemove = [$currencyDay7_7];
            $this->fetchRates($currencyDay7_7);

            if (empty($this->rates)) {
                return;
            }

            $this->fxrates['day14+7'][$currencyDay7_7]['2020-03-23'] = $this->rates;

            $val1 = $this->addCurrencyConversionCost($this->fxrates['day14+7'][$currencyDay7_7]['2020-03-23']['2020-03-23'][$this->baseCurrencyOriginal]);
            $qtyCurrency = $this->convertBaseToCurrency($this->baseCurrencyOriginal, $qty, $val1);
        } catch (\Exception $ex) {
        } finally {
            if (!isset($ex) && !empty($qtyCurrency)) {
                $this->currenciesResult['day14+7'][$this->dateToday]['currenciesResult'][$this->baseCurrencyOriginal][$currencyDay1_7][$currencyDay7_7] = round($qtyCurrency - $this->qtyOriginal, 2);
            }
        }
        /////////////////////////////////////////////

        print_r($this->currenciesResult['day14+7'][$this->dateToday]['currenciesResult']);
    }
}