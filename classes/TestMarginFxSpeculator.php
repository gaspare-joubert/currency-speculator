<?php

/**
 * Class TestMarginFxSpeculator
 *
 * Calculate Delayed Margin
 * Using maximum 1 iteration
 * Use a set start day
 * Use 7 day intervals
 * Use a set minimum appreciation level
 *
 * Fetch rates on day1
 * baseCurrency = $baseCurrencyOriginal
 *
 * Fetch rates on day1 + 7days
 * baseCurrency = $baseCurrencyOriginal
 *
 * Compare rates for day1 and day7
 * Find rate with 10% +  depreciation
 * Convert the original qty of Currency units to Currency
 * baseCurrency = Currency
 * Fetch rates
 *
 *
 *
 *
 *
 *
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
class TestMarginFxSpeculator Extends FxSpeculator
{
    private $day1;
    private $dayNext;

    private $fxrates = array();
    private $currencies01 = array();
    private $currencies02 = array();
    private $currencies03 = array();

    /**
     * TestMarginFxSpeculator constructor.
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
        $this->date = new DateTime('2020-01-01');
        $this->day1 = $this->date;
        $this->setDay1($this->day1);
        $this->setDayNext($this->day1);
        $this->getFxAppreciation();
    }

    /**
     * @param DateTime $day1
     * @return mixed
     *
     * Set day 1 of the session
     */
    private function setDay1(DateTime $day1)
    {
        try {
            $this->dateToday = $day1->format('Y-m-d');
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->baseCurrencyOriginal);
        } catch (\Exception $ex) {
        }

        while (empty($this->rates)) {
            $day1 = $day1->add(new DateInterval('P1D'));
            return $this->setDay1($day1);
        }

        $this->day1 = $day1;
        return $this->fxrates['day1'][$this->baseCurrencyOriginal] = $this->rates;
    }

    /**
     * @param DateTime $dayNext
     * @return mixed
     *
     * Set the next day of the session
     */
    private function setDayNext(DateTime $paramDayNext)
    {
        $dayNext = '';
        try {
            $dayNext = new DateTime($paramDayNext->format('Y-m-d'));
            $dayNext = $dayNext->add(new DateInterval('P1D'));
            $this->dateToday = $dayNext->format('Y-m-d');
            $this->dateYesterday = $this->dateToday;
            $this->fetchRates($this->baseCurrencyOriginal);
        } catch (\Exception $ex) {
        }

        while (empty($this->rates)) {
            return $this->setDayNext($dayNext);
        }

        $this->dayNext = $dayNext;
        return $this->fxrates['dayNext'][$this->baseCurrencyOriginal] = $this->rates;
    }

    /**
     * Compare fx rates of day1 and dayNext
     * Find currency with appreciation of more than 12.5%
     *
     * If none is found, move nextDay by one day
     */

    private function getFxAppreciation()
    {
        $day1 = $this->day1->format('Y-m-d');
        $dayNext = $this->dayNext->format('Y-m-d');
        foreach ($this->fxrates['day1'][$this->baseCurrencyOriginal][$day1] as $key => $val) {
            $val1 = $this->fxrates['dayNext'][$this->baseCurrencyOriginal][$dayNext][$key];
            $appreciation = '';

            if($val1 > $val) {
                $appreciation = round(((($val1 / $val) * 100) - 100) , 2);

                if($appreciation >= '12.5') {
                    $this->appreciation01['dayNext'][$this->baseCurrencyOriginal][$dayNext][$key] = $appreciation;
                }
            }
        }
    }
}