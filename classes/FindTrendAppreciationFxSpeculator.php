<?php

/**
 * Class FindTrendAppreciationFxSpeculator
 *
 * Use a set start date
 * find a currency which the $baseCurrencyOriginal has appreciated by $minimumAppreciationFx against
 * base currency = $baseCurrencyOriginal
 */
class FindTrendAppreciationFxSpeculator Extends FxSpeculator
{
    private $day1;
    private $dayNext;
    private $fetchRatesCurrency;
    private $minimumAppreciationFx = '0'; // Minimum appreciation to trigger conversion from original base currency
    private $endDate;
    private $dateInterval = 'P0D'; // 'P1D' 'P1W' 'P1M' 'P1Y'
    private $getFxAppreciationSubMsg;
    private $appreciation01 = array();

    public function __construct()
    {
        parent::__construct($GLOBALS["fxSpeculator"]->exchangeRatesAPI);
        $this->baseCurrencyOriginal = 'GBP';
        $this->main();
    }

    private function main()
    {
        /**
         * For testing only!!
         */
        $startDate = '2020-05-21';
        $this->date = new DateTime($startDate);

        //$this->date = new DateTime(); // Use this for live instance
        $this->minimumAppreciationFx = '38.2'; // 23.6, 38.2, 50.0, 61.8, 76.4
        $this->dateInterval = 'P1Y'; // 'P1D' 'P1W' 'P1M' 'P1Y'
        $this->endDate = clone $this->date;
        $this->endDate = $this->endDate->sub(new DateInterval($this->dateInterval));
        $this->day1 = $this->date;
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        if (!($this->setDay1Sub($this->day1))) {
            echo "setDay1Sub unable to fetch rates for $this->dateToday. Try again after CET 16:00.";
            exit();
        }
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        if (!($this->setDayNextSub($this->day1))) {
            echo "setDayNextSub: Rates not fetched between $this->day1 and $this->endDate.";
            exit();
        }
        $this->fetchRatesCurrency = $this->baseCurrencyOriginal;
        if (($test = $this->getFxAppreciationSub($this->dayNext)) == ('msg1' || 'msg2')) {
            echo "$this->getFxAppreciationSubMsg";
            exit();
        }
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
     * Move the next day back by one day up to $endDate, until rates are fetched
     */
    private function setDayNextSub(DateTime $paramDayNext)
    {
        $dayNext = '';
        try {
            $dayNext = new DateTime($paramDayNext->format('Y-m-d'));

            if ($this->dateInterval != 'P0D') {
                if (empty($this->rates) && $this->endDate >= $paramDayNext  ) {
                    return false;
                }
            }

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
     * If none is found, move nextDay back by one day up to $endDate
     */
    private function getFxAppreciationSub(DateTime $paramDayNext)
    {
        $dayNext = '';
        $appreciation = '';
        $msg1 = false;
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

        while (empty($this->appreciation01['day1'][$day1][$this->fetchRatesCurrency][$dayNext->format('Y-m-d')]) && $this->endDate <= $dayNext  ) {
            if(!($this->setDayNextSub($paramDayNext))) {
                $msg1 = true;
                break; // Rates not fetched between $this->day1 and $this->endDate
            }
            return $this->getFxAppreciationSub($this->dayNext);
        }

        if (empty($this->appreciation01['day1']) && $msg1) {
            $this->getFxAppreciationSubMsg = "getFxAppreciationSub: Rates not fetched between $day1 and " .$this->endDate->format('Y-m-d') .".\n";
            $this->getFxAppreciationSubMsg .= "No fx found with $this->minimumAppreciationFx% appreciation against $this->baseCurrencyOriginal.";
            return 'msg1';
        }

        if (empty($this->appreciation01['day1']) && !$msg1) {
            $this->getFxAppreciationSubMsg = "getFxAppreciationSub: Rates fetched between $day1 and " .$this->endDate->format('Y-m-d') .".\n";
            $this->getFxAppreciationSubMsg .= "No fx found with $this->minimumAppreciationFx% appreciation against $this->baseCurrencyOriginal.";
            return 'msg2';
        }

        $this->getFxAppreciationSubMsg = "getFxAppreciationSub: Rates fetched between $day1 and " .$this->endDate->format('Y-m-d') .".\n";
        $this->getFxAppreciationSubMsg .= "Fx found with $this->minimumAppreciationFx% appreciation against $this->baseCurrencyOriginal.\n";
        $this->getFxAppreciationSubMsg .= json_encode($this->appreciation01);

        var_dump($this->getFxAppreciationSubMsg);
    }
}