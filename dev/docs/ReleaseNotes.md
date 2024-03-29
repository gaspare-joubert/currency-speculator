# Currency Speculator - Release Notes

## 2020-07-27 v0.5.1
#### [Story: Find Trend Profit Point Fx Speculator]
- **Minor Update**
    - Class FindTrendProfitPointFxSpeculator
        - Add return type for function main()
        - In function outputNewCurrencyRateSell() use $appreciationCostAsPercentageTotal in $newCurrencyRateSellMsg 
          so that same percentage used in file name and file content

## 2020-06-12 v0.5
#### [Story: Find Trend Profit Point Fx Speculator]
- **Add Feature**
    - Class FindTrendProfitPointFxSpeculator
        - Use the rate used to purchase a currency and calculate the Profit Point for this transaction
          Use the minimum profit percentage and cost of converting currency
          Output information to a text file
    - Interface AppreciationInterface
        - Used to declare methods needed to calculate the Fx profit point

## 2020-06-09 v0.4.1
#### [Story: Find Trend Fx Speculator]
- **Add Feature**
     - Class FindTrendAppreciationFxSpeculator
         - Write Fx trend to a text file

## 2020-06-05 v0.4
#### [Story: Find Trend Fx Speculator]
- **Add Feature**
     - Class FindTrendAppreciationFxSpeculator
         - Find Fx which has depreciated by a given percentage against the base currency
            - Set a date range to search
              Set a minimum depreciation percentage to search for

## 2020-05-22 v0.3
#### [Story: Live Margin Fx Speculator]
- **Add Feature**
    - Class LiveMarginFxSpeculator
        - Calculate Live Margin
         - Use the first part of the process
           - to find a currency which has depreciated by $minimumAppreciationFx
           - against the $baseCurrencyOriginal
           - convert a $qtyOriginal of the $baseCurrencyOriginal to this $newCurrency
         - Use the second part of the process
           - to find the date when the $newCurrency has appreciated by $minimumAppreciationBaseCurrencyOriginal against the $baseCurrencyOriginal
           - convert the $newCurrency back to the $baseCurrencyOriginal

## 2020-05-15 v0.2
#### [Story: Test Margin Fx Speculator]
- **Add Feature**
    - Class TestMarginFxSpeculator
        - Calculate Margin over period 
        - Using a given start date
        - Using maximum 1 iterations
        - Using a minimum appreciation

## 2020-05-08 v0.1
#### [Story: Immediate Margin Fx Speculator]
- **Add Feature**
    - Class ImmediateMarginFxSpeculator
        - Calculate Immediate Margin
        - Using maximum 1 iterations
    - Class DelayedMarginFxSpeculator
       - Calculate Margin over a period of 3 consecutive weeks
