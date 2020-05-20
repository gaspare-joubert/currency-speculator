<?php

/**
 * Class LiveMarginFxSpeculator
 *
 * Calculate Live Margin
 * Use the first part of the process
 * * to find a currency which has depreciated by $minimumAppreciationFx
 * * against the $baseCurrencyOriginal
 * * convert a $qtyOriginal of the $baseCurrencyOriginal to this $newCurrency
 * Use the second part of the process
 * * to find the date when the $newCurrency has appreciated by $minimumAppreciationBaseCurrencyOriginal against the $baseCurrencyOriginal
 * * convert the $newCurrency back to the $baseCurrencyOriginal
 *
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
    private $minimumAppreciationFx = '15'; // Minimum appreciation to trigger conversion from original base currency
    private $minimumAppreciationBaseCurrencyOriginal = '1'; // Minimum appreciation when converting currency back to original base currency
    private $key1;
    private $now;
    private $newCurrency;
    private $newCurrencyQty;

    public function __construct()
    {
        parent::__construct($GLOBALS["fxSpeculator"]->exchangeRatesAPI);
        $this->baseCurrencyOriginal = 'GBP';
        $this->now = new DateTime();
        $this->now = $this->now->setTime('0','0','0');
        $this->main();
    }

    private function main()
    {
        /**
         * For testing only!!
         */
        $startDate = '2020-05-06';
        $this->date = new DateTime($startDate);

        //$this->date = new DateTime(); // Use this for live instance
        $this->day1 = $this->date;
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        if (!($this->setDay1Sub($this->day1))) {
            echo "setDay1Sub unable to fetch rates for $this->dateToday. Try again after CET 16:00.";
            exit();
        }
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        $this->setDayNextSub($this->day1);
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        $this->getFxAppreciationSub($this->dayNext);
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        $this->convertCurrencySub();
        /**
         * Manually record the details for this session
         * The original base currency has now been converted
         * Now find the next day when this currency has appreciated by 12.5% against the original base currency
         */
        $newCurrencyConversionDate = new DateTime('2020-05-06'); // Use from $this->currencies01
        $this->newCurrency = 'BRL'; // Use from $this->currencies01
        $this->newCurrencyQty = '6804.76'; // Use from $this->currencies01
        $this->fetchRatesCurrency = $this->newCurrency;
        if(!($this->setDayNextAdd($newCurrencyConversionDate))) {
            echo "setDayNextAdd Failed to fetch rates. Possibly due to newCurrencyConversionDate being the same as today's date.";
            exit();
        }
        $this->fetchRatesCurrency = $this->newCurrency;
        if(!($this->getFxAppreciationBaseCurrencyOriginal($this->dayNext))) {
            $newCurrencyConversionDate = $newCurrencyConversionDate->format('Y-m-d');
            echo "getFxAppreciationBaseCurrencyOriginal: The minimum appreciation ($this->minimumAppreciationBaseCurrencyOriginal%) of $this->newCurrency between $newCurrencyConversionDate and today has not been found.";
            exit();
        }
        $this->fetchRatesCurrency = $this->newCurrency;
        $this->convertCurrencyBaseCurrencyOriginal();
    }

    /**
     * @param DateTime $day1
     * @return mixed
     *
     * Set day 1 of the session
     * Fetch rates for day 1
     *
     * Use today as the start date
     * If rates are not available for today, exit
     */
    private function setDay1Sub(DateTime $day1)
    {
        try {
            $this->dateToday = $day1->format('Y-m-d');
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->fetchRatesCurrency);

            if(empty($this->rates)) {
                return false;
            }

        } catch (\Exception $ex) {
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

                    if($appreciation >= $this->minimumAppreciationFx) {
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
                    $this->currencies01['day1'][$this->fetchRatesCurrency][$this->day1->format('Y-m-d')]['fxRate'] = $this->fxrates['day1'][$this->fetchRatesCurrency][$this->day1->format('Y-m-d')][$key1];
                    $this->currencies01['day1'][$this->fetchRatesCurrency][$this->day1->format('Y-m-d')]['fxRateWithCost'] = $val1;
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

        while (empty($this->rates) && $dayNext < $this->now) {
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

                if($appreciation >= $this->minimumAppreciationBaseCurrencyOriginal) {
                    $this->appreciation02['dayNext'][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')][$this->baseCurrencyOriginal] = $appreciation;
                }
            }
        } catch (\Exception $ex) {
        }

        while (empty($this->appreciation02['dayNext'][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')])) {
            if(!($this->setDayNextAdd($paramDayNext))) {
                return false;
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

        $this->dateToday = '2020-05-19';

        //$this->dateToday = $this->now->format('Y-m-d');
        $this->dateYesterday = $this->dateToday;
        $this->rates = $this->exchangeRatesAPI->addRate('GBP')->setBaseCurrency($this->fetchRatesCurrency)->addDateFrom($this->dateYesterday)->addDateTo($this->dateToday)->fetch()->getRates();
        if(empty($this->rates)) {
            echo "Rate for $this->fetchRatesCurrency ($this->dateToday) could not be fetched. Try again after CET 16:00.";
            exit();
        }
        $val1 = $this->addCurrencyConversionCost($this->rates[$this->dateToday][$this->baseCurrencyOriginal]); // Using today's rate
        $qty = $this->newCurrencyQty;

        try {
            $qtyCurrency = $this->convertBaseToCurrency($key1, $qty, $val1);
        } catch (\Exception $ex) {
        } finally {
            if (!isset($ex) && !empty($qtyCurrency)) {
                $result = $this->currenciesResult['currenciesResult'][$this->day1->format('Y-m-d')][$this->baseCurrencyOriginal][$this->fetchRatesCurrency][$this->dayNext->format('Y-m-d')][$this->fetchRatesCurrency][$this->baseCurrencyOriginal] = round($qtyCurrency - $this->qtyOriginal, 2);
            }
        }

        $appreciation01Date = key($this->appreciation01['day1']);
        $appreciation01FxRate = round($this->fxrates['day1'][$this->fetchRatesCurrency][$appreciation01Date][$this->baseCurrencyOriginal], 4);
        $appreciation02Date = key($this->appreciation02['dayNext'][$this->fetchRatesCurrency]);
        $appreciation02Rate = round($this->rates[$this->dateToday][$this->baseCurrencyOriginal], 4);

        $result = "On $appreciation01Date converted ".(string)$this->baseCurrencyOriginal.(string)$this->qtyOriginal .' to ' .(string)$this->newCurrency.(string)$this->newCurrencyQty ."<br/>";
        $result .= "The $this->newCurrency rate was $appreciation01FxRate against $this->baseCurrencyOriginal.<br/>";
        $result .= "On $appreciation02Date the minimum appreciation ($this->minimumAppreciationBaseCurrencyOriginal%) has been found.<br/>";
        $result .= "It is recommended to convert back to $this->baseCurrencyOriginal at $appreciation02Rate.<br/>";

        $profitLoss = round($qtyCurrency - $this->qtyOriginal, 2);

        if($profitLoss == 0) {
            $result .= "Resulting in no gain or loss.";
        } elseif ($profitLoss < 0) {
            $result .= "Resulting in a LOSS of $this->baseCurrencyOriginal" .-($profitLoss) .' .';
        } elseif ($profitLoss > 0) {
            $result .= "Resulting in a PROFIT of $this->baseCurrencyOriginal$profitLoss.";
        }

        print_r($this->currenciesResult['currenciesResult']);
        echo '<br/>';
        echo '<br/>';
        echo ('-----------------------');
        echo '<br/>';
        echo '<br/>';
        var_export($result);
        echo '<br/>';
        echo '<br/>';
        echo ('-----------------------');
    }
}