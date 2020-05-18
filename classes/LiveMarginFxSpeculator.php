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

    public function __construct()
    {
        parent::__construct($GLOBALS["fxSpeculator"]->exchangeRatesAPI);
        $this->baseCurrencyOriginal = 'GBP';
        $this->main();
    }

    private function main()
    {
        $this->date = new DateTime('2019-01-01');
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
        $startDate =  new DateTime('2019-01-01');
        $newCurrency = 'TRY';
        $newCurrencyQty = '6603.84';
        $this->fetchRatesCurrency = $newCurrency;
        $this->setDay1Add($startDate);
    }

    /**
     * @param DateTime $day1
     * @return mixed
     *
     * Set day 1 of the session
     * Fetch rates for day 1
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
     * Find currency with appreciation of more than 12.5% against original base currency
     *
     * If none is found, move nextDay by one day
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
     * @param DateTime $day1
     * @return mixed
     *
     * Set day 1 of the session
     * Fetch rates for day 1
     */
    public function setDay1Add(DateTime $day1)
    {
        try {
            $this->dateToday = $day1->format('Y-m-d');
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->fetchRatesCurrency);
        } catch (\Exception $ex) {
        }

        while (empty($this->rates)) {
            $day1 = $day1->add(new DateInterval('P1D'));
            return $this->setDay1Add($day1);
        }

        $this->day1 = $day1;
        return $this->fxrates['day1'][$this->fetchRatesCurrency] = $this->rates;
    }
}