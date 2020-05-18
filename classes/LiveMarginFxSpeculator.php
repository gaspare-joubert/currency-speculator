<?php

/**
 * Class LiveMarginFxSpeculator
 *
 * Calculate Delayed Margin
 * Using maximum 1 currency conversion
 * Use today and set start day ($day1)
 * Use one day intervals into the past ($nextDay)
 * Use a set minimum appreciation level
 * Convert currency back to original base currency only when a minimum appreciation level
 *
 * Fetch rates on day1
 * baseCurrency = $baseCurrencyOriginal
 *
 * Fetch rates on nextDay
 * baseCurrency = $baseCurrencyOriginal
 *
 * Compare rates for day1 and dayNext
 * Find rate with 10% + 2.5% depreciation
 * Convert the original qty of Currency units to Currency
 * baseCurrency = Currency
 * Fetch rates
 *
 * Fetch rates on new nextDay
 * If currency has appreciated by 12.5% to original base currency
 * Convert currency to original base currency
 * Calculate Profit/Loss

 */
class LiveMarginFxSpeculator Extends FxSpeculator
{
    private $day1;
    private $dayNext;
    private $fetchRatesCurrency;
    private $minimumAppreciation = '15';
    private $key1;
    private $now;

    public function __construct()
    {
        parent::__construct($GLOBALS["fxSpeculator"]->exchangeRatesAPI);
        $this->baseCurrencyOriginal = 'GBP';
        $this->now = new DateTime('now');
        $this->main();
    }

    private function main()
    {
        $startDate = '2020-05-18';
        $this->date = new DateTime($startDate);
        $this->day1 = $this->date;
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        $this->setDay1Sub($this->day1);
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        $this->setDayNextSub($this->day1);
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        $this->getFxAppreciationSub($this->dayNext);
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        $this->convertCurrencySub();
        /**
         * The original base currency has now been converted
         * Now find the next day when this currency has appreciated by 12.5% against the original base currency
         */
        $newCurrencyStartDate = new DateTime($this->dateToday);
        $newCurrency = 'BRL';
        $newCurrencyQty = '6857.14';
        $this->fetchRatesCurrency = $newCurrency;
        $this->setDayNextAdd($newCurrencyStartDate);
        $this->fetchRatesCurrency = $newCurrency;
        $this->getFxAppreciationBaseCurrencyOriginal($this->dayNext);
        $this->fetchRatesCurrency = $newCurrency;
        $this->convertCurrencyBaseCurrencyOriginal();
    }

    /**
     * @param DateTime $day1
     * @return mixed
     *
     * Set day 1 of the session
     * Fetch rates for day 1
     *
     * Use a set start date
     * Keep moving the day 1 back by one day, until rates are fetched
     */
    private function setDay1Sub(DateTime $day1)
    {
        try {
            $this->dateToday = $day1->format('Y-m-d');
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->fetchRatesCurrency);
        } catch (\Exception $ex) {
        }

        while (empty($this->rates)) {
            $day1 = $day1->sub(new DateInterval('P1D'));
            return $this->setDay1Sub($day1);
        }

        $this->day1 = $day1;
        return $this->fxrates['day1'][$this->fetchRatesCurrency] = $this->rates;
    }

    /**
     * @param DateTime $paramDayNext
     * @return mixed
     *
     * Set the next day of the session
     * Fetch rates for the next day
     *
     * Keep moving the next day back by one day, until rates are fetched
     */
    private function setDayNextSub(DateTime $paramDayNext)
    {
        $dayNext = '';
        try {
            $dayNext = new DateTime($paramDayNext->format('Y-m-d'));
            $dayNext = $dayNext->sub(new DateInterval('P1D'));
            $this->dateToday = $dayNext->format('Y-m-d');
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->fetchRatesCurrency);
        } catch (\Exception $ex) {
        }

        while (empty($this->rates)) {
            return $this->setDayNextSub($dayNext);
        }

        $this->dayNext = $dayNext;
        return $this->fxrates['dayNext'][$this->fetchRatesCurrency] = $this->rates;
    }

    /**
     * @param DateTime $paramDayNext
     * @return DateTime
     *
     * Compare fx rates of day1 and dayNext
     * Find currency with appreciation of more than $minimumAppreciation against original base currency
     *
     * If none is found, move nextDay back by one day
     */
    private function getFxAppreciationSub(DateTime $paramDayNext)
    {
        $dayNext = '';
        $appreciation = '';
        try {
            $dayNext = new DateTime($paramDayNext->format('Y-m-d'));
            $day1 = $this->day1->format('Y-m-d');
            foreach ($this->fxrates['day1'][$this->fetchRatesCurrency][$day1] as $key => $val) {
                $val1 = $this->fxrates['dayNext'][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')][$key];

                if($val > $val1) {
                    $appreciation = round(((($val / $val1) * 100) - 100) , 2);

                    if($appreciation >= $this->minimumAppreciation) {
                        $this->appreciation01['day1'][$day1][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')][$key] = $appreciation;
                    }
                }
            }
        } catch (\Exception $ex) {
        }

        while (empty($this->appreciation01['day1'][$day1][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')])) {
            $this->setDayNextSub($paramDayNext);
            return $this->getFxAppreciationSub($this->dayNext);
        }

        return $this->dayNext = $paramDayNext;
    }

    /**
     * Convert the original base currency to the appreciated currency
     */
    private function convertCurrencySub(): void
    {
        if (asort($this->appreciation01['day1'][$this->day1->format('Y-m-d')][$this->fetchRatesCurrency][$this->dayNext->format('Y-m-d')])) {
            reset($this->appreciation01['day1'][$this->day1->format('Y-m-d')][$this->fetchRatesCurrency][$this->dayNext->format('Y-m-d')]);
            $key1 = key($this->appreciation01['day1'][$this->day1->format('Y-m-d')][$this->fetchRatesCurrency][$this->dayNext->format('Y-m-d')]);
            $this->key1 = $key1;
            $val1 = $this->addCurrencyConversionCost($this->fxrates['day1'][$this->fetchRatesCurrency][$this->day1->format('Y-m-d')][$key1]);

            $this->ratesToRemove = [$key1];

            try {
                $qtyCurrency = $this->convertBaseToCurrency($key1, $this->qtyOriginal, $val1);
            } catch (\Exception $ex) {
            } finally {
                if (!isset($ex) && !empty($qtyCurrency)) {
                    try {
                        $this->dateToday = $this->day1->format('Y-m-d');
                        $this->dateYesterday = $this->dateToday;
                        $this->fetchRates($key1);
                    } catch (\Exception $ex) {
                    }

                    if (empty($this->rates)) {
                        return;
                    }

                    $this->fxrates['day1'][$key1] = $this->rates;
                    $this->currencies01['day1'][$this->fetchRatesCurrency][$this->day1->format('Y-m-d')][$key1] = $qtyCurrency;
                }
            }
        }
    }

    /**
     * @param DateTime $paramDayNext
     * @return mixed
     *
     * Set the next day of the session
     * Fetch rates for the next day
     *
     * Keep moving the next day forward by one day, until rates are fetched
     */
    private function setDayNextAdd(DateTime $paramDayNext)
    {
        $dayNext = '';
        try {
            $dayNext = new DateTime($paramDayNext->format('Y-m-d'));
            $dayNext = $dayNext->add(new DateInterval('P1D'));
            $this->dateToday = $dayNext->format('Y-m-d');
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->fetchRatesCurrency);
        } catch (\Exception $ex) {
        }

        while (empty($this->rates) && $dayNext <= $this->now) {
            return $this->setDayNextAdd($dayNext);
        }

        if (!empty($this->rates)) {
            $this->dayNext = $dayNext;
            return $this->fxrates['dayNext'][$this->fetchRatesCurrency] = $this->rates;
        } else {
            return false;
        }
    }

    /**
     * @param DateTime $paramDayNext
     * @return DateTime
     *
     * Compare fx rates of day1 and dayNext
     * Find appreciation of more than $minimumAppreciation of the converted currency against original base currency
     *
     * If none is found, move nextDay forward by one day
     */
    private function getFxAppreciationBaseCurrencyOriginal(DateTime $paramDayNext)
    {
        $dayNext = '';
        $appreciation = '';
        try {
            $dayNext = new DateTime($paramDayNext->format('Y-m-d'));
            $day1 = $this->day1->format('Y-m-d');
            $val = $this->fxrates['day1'][$this->fetchRatesCurrency][$day1][$this->baseCurrencyOriginal];
            $val1 = $this->fxrates['dayNext'][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')][$this->baseCurrencyOriginal];

            if($val1 > $val) {
                $appreciation = round(((($val1 / $val) * 100) - 100) , 2);

                if($appreciation >= $this->minimumAppreciation) {
                    $this->appreciation02['dayNext'][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')][$this->baseCurrencyOriginal] = $appreciation;
                }
            }
        } catch (\Exception $ex) {
        }

        while (empty($this->appreciation02['dayNext'][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')])) {
            if(!($this->setDayNextAdd($paramDayNext))) {
                break;
            } else {
                return $this->getFxAppreciationBaseCurrencyOriginal($this->dayNext);
            }
        }

        return $this->dayNext = $paramDayNext;
    }

    /**
     * Convert the new currency to original base currency
     */
    private function convertCurrencyBaseCurrencyOriginal(): void
    {
        $key1 = $this->baseCurrencyOriginal;
        $val1 = $this->addCurrencyConversionCost($this->fxrates['dayNext'][$this->fetchRatesCurrency][$this->dayNext->format('Y-m-d')][$key1]);
        $qty = $this->currencies01['day1'][$this->baseCurrencyOriginal][$this->day1->format('Y-m-d')][$this->fetchRatesCurrency];

        try {
            $qtyCurrency = $this->convertBaseToCurrency($key1, $qty, $val1);
        } catch (\Exception $ex) {
        } finally {
            if (!isset($ex) && !empty($qtyCurrency)) {
                $result = $this->currenciesResult['currenciesResult'][$this->day1->format('Y-m-d')][$this->baseCurrencyOriginal][$this->fetchRatesCurrency][$this->dayNext->format('Y-m-d')][$this->fetchRatesCurrency][$this->baseCurrencyOriginal] = round($qtyCurrency - $this->qtyOriginal, 2);
            }
        }

        print_r($this->currenciesResult['currenciesResult']);
    }
}