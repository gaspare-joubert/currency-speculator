<?php

/**
 * Interface AppreciationInterface
 *
 * Methods to implement:
 * * newCurrencyRateBuy
 * * minimumAppreciationFx
 * * conversionCost
 * * adminCost
 * * calculateAppreciationCostAsPercentage
 */
interface AppreciationInterface
{
    /**
     * @return mixed
     *
     * The rate used to convert the baseCurrencyOriginal to the newCurrency
     * Returned as float (with precision = 2)
     */
    public function newCurrencyRateBuy();

    /**
     * @return mixed
     *
     * Minimum appreciation to trigger conversion of currency
     * Returned as a percentage (%)
     */
    public function minimumAppreciationFx();

    /**
     * @return mixed
     *
     * Rate charged by the Fx agency to convert one currency to another
     * Returned as a percentage (%)
     */
    public function conversionCost();

    /**
     * @return mixed
     *
     * Any admin costs associated with converting the currency
     * Calculated and returned as a percentage (%)
     */
    public function adminCost();

    /**
     * CalculateAppreciationCostAsPercentage constructor.
     *
     * Total cost to be used to calculate rate needed for conversion
     * Calculated and returned as a percentage (%)
     */
    public function calculateAppreciationCostAsPercentage();
}