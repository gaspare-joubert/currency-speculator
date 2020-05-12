<?php

/**
 * Class ImmediateMarginFxSpeculator
 *
 * Calculate Immediate Margin
 * Using maximum 1 iteration
 *
 * Fetch rates
 * baseCurrency = $baseCurrencyOriginal
 *
 * Foreach Currency
 * Convert the original qty of Currency units to Currency
 *
 * For 1 iteration
 * baseCurrency = Currency
 * Find baseCurrency's highest conversion Currency
 * Convert to Currency
 *
 * Convert Currency to GBP
 * Calculate Profit/Loss
 */
class ImmediateMarginFxSpeculator Extends FxSpeculator
{
    private $currencies = array();
    private $currencies01 = array();
    private $currencies02 = array();
    private $currencies03 = array();

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

        $this->currencies = $this->rates;

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

                if (isset($ex) && $ex->getMessage() == 'The specified currency code is not currently supported.') {
                    unset($ex);
                }
            }
        }

        if (empty($this->currencies01[$this->dateToday]['currencies01'])) {
            return;
        }

        /////////////////////////////////////////////

        /**
         * For 1 iteration
         * baseCurrency = Currency
         * Find baseCurrency's highest conversion Currency
         * Convert to Currency
         */
        foreach ($this->currencies01[$this->dateToday]['currencies01'] as $key => $val) {
            $baseCurrency = $key;
            $this->ratesToRemove = [$this->baseCurrencyOriginal, $key];

            try {
                $this->fetchRates($baseCurrency);
                //$this->fetchRatesWithRemovedRate($baseCurrency, $key); // For Testing Only!!
            } catch (\Exception $ex) {
            }

            $this->currencies02[$this->dateToday]['currencies02'][$key] = $this->rates;
            //$this->rates = $this->exchangeRatesAPI->addRate($key)->setBaseCurrency($this->baseCurrencyOriginal)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch(); // For Testing Only!!
        }

        if (empty($this->currencies02[$this->dateToday]['currencies02'])) {
            return;
        }

        foreach ($this->currencies02[$this->dateToday]["currencies02"] as $key => $val) {

            if(arsort($val[$this->dateToday])) {
                $val1 = reset($val[$this->dateToday]);
                $key1 = key($val[$this->dateToday]);

                /**
                 * 1st Iteration
                 */
                try {
                    $qty = $this->currencies01[$this->dateToday]['currencies01'][$key];
                    $qtyCurrency = $this->convertBaseToCurrency($key1, $qty, $val1);
                } catch (\Exception $ex) {
                } finally {
                    if (!isset($ex) && !empty($qtyCurrency)) {
                        $this->currencies03['01'][$this->dateToday]['currencies03_01'][$key][$key1] = $qtyCurrency;
                    }
                }
            }
        }

        if (empty($this->currencies03)) {
            return;
        }

        /**
         * Convert Currency to GBP
         * Calculate Profit/Loss
         */
        foreach ($this->currencies03['01'][$this->dateToday]["currencies03_01"] as $key => $val) {
            try {
                $val1 = reset($val);
                $key1 = key($val);

                $rateInverted = 1/($this->currencies[$this->dateToday][$key1]);
                $qtyCurrency = $this->convertBaseToCurrency($this->baseCurrencyOriginal, $val1, $rateInverted);
            } catch (\Exception $ex) {
            } finally {
                if (!isset($ex) && !empty($qtyCurrency)) {
                    $this->currenciesResult[$this->dateToday]['currenciesResult'][$this->baseCurrencyOriginal][$key][$key1] = $qtyCurrency - $this->qtyOriginal;
                }
            }
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