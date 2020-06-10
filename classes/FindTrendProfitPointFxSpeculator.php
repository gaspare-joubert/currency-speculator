<?php

require_once('classes/AppreciationInterface.php');

/**
 * Class FindTrendProfitPointFxSpeculator
 *
 * Use the rate used to purchase a currency
 * Calculate the Profit Point for this transaction
 * Must factor in minimum profit percentage
 * Must factor in cost of converting currency
 * Must output information to a text file
 */
class FindTrendProfitPointFxSpeculator implements AppreciationInterface
{
    private $qtyOriginal; // The number of the original currency units exchanged
    private $baseCurrencyOriginal; // The original currency used to convert to the new currency
    private $newCurrencyQty;
    private $newCurrency;
    private $newCurrencyRateSell;
    private $appreciationCostAsPercentageTotal;

    public function __construct()
    {
        $this->main();
    }

    private function main()
    {
        $this->baseCurrencyOriginal = 'GBP';
        $this->qtyOriginal = '1000';
        $this->newCurrencyQty = '7120.00';
        $this->newCurrency = 'BRL';
        $this->newCurrencyRateSell = $this->calculateNewCurrencyRateSell();
        $this->outputnewCurrencyRateSell();
    }

    /**
     * @return void
     *
     * Write newCurrencyRateSell to text file
     */
    private function outputNewCurrencyRateSell(): void
    {
        $newCurrencyRateSellMsg = "You converted $this->baseCurrencyOriginal$this->qtyOriginal to $this->newCurrency$this->newCurrencyQty at a rate of " .$this->newCurrencyRateBuy() .". \n";
        $newCurrencyRateSellMsg .= "The goal is to achieve a minimum appreciation of 12.1%, including cost. \n";
        $newCurrencyRateSellMsg .= "It is recommended to covert BRL back to GBP at a rate of $this->newCurrencyRateSell. \n";
        $newCurrencyRateSellMsg .= "This should result in a total of $this->baseCurrencyOriginal" . round($this->newCurrencyQty/$this->newCurrencyRateSell, 2);

        $fileDirectory = '../CurrencySpeculator/ProfitPoint/';
        $fileName = (string)$this->newCurrency . (string)$this->newCurrencyQty . '_bought_at_' . $this->newCurrencyRateBuy(
            ) . '_' .round($this->appreciationCostAsPercentageTotal, 2) .'%.txt';
        $fullPath = $fileDirectory . $fileName;

        try {
            $handle = file_put_contents($fullPath, $newCurrencyRateSellMsg);
            chmod($fullPath, 0775);
        } catch (\Exception $ex) {
        }
    }

    /**
     * @return false|float
     *
     * Based on the rate used to convert the baseCurrencyOriginal to the newCurrency
     * Use AppreciationCostAsPercentage
     * Calculate the rate at which to convert the newCurrency to the baseCurrencyOriginal
     */
    private function calculateNewCurrencyRateSell()
    {
        return round(
            $this->newCurrencyRateBuy() * ($this->calculateAppreciationCostAsPercentage()),
            2
        );
    }

    /**
     * @return mixed
     *
     * The rate used to convert the baseCurrencyOriginal to the newCurrency
     * Returned as float (with precision = 2)
     */
    public function newCurrencyRateBuy()
    {
        return 7.12;
    }

    /**
     * @return mixed
     *
     * Minimum appreciation to trigger conversion of currency
     * Returned as a percentage (%)
     */
    public function minimumAppreciationFx()
    {
        return 12.1;
    }

    /**
     * @return mixed
     *
     * Rate charged by the Fx agency to convert one currency to another
     * Returned as a percentage (%)
     */
    public function conversionCost()
    {
        return 2.5;
    }

    /**
     * @return mixed
     *
     * Any admin costs associated with converting the currency
     * Calculated and returned as a percentage (%)
     */
    public function adminCost()
    {
        return 2.99 / 7210; // The cost quoted by the FX agency to deliver the FX to residence was £2.99 for BRL7210
    }

    /**
     * CalculateAppreciationCostAsPercentage constructor.
     *
     * Total cost to be used to calculate rate needed for conversion
     * Calculated and returned as a percentage (%)
     */
    public function calculateAppreciationCostAsPercentage()
    {
        $this->appreciationCostAsPercentageTotal = $this->minimumAppreciationFx() + $this->conversionCost(
            ) + $this->adminCost();

        return (100 - ($this->appreciationCostAsPercentageTotal)) / 100;
    }
}