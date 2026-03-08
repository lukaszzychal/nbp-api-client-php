# QA Manual Testing Guide - NBP API Client

This guide provides instructions for manual testing of the NBP API Client library.

## Test Areas

### 1. API Connectivity & Resilience

**Scenario: API is Down (500 Error)**
- **Tool**: Mock the NBP API response to return a 500 status code.
- **Expectation**: The library should retry 3 times (Retry Strategy) before throwing a `RuntimeException`.
- **Validation**: Check logs if a PSR-3 Logger is attached.

### 2. Data Integrity (Floating Point Errors)

**Scenario: Very Small Currency Rates (Exotic)**
- **Source**: Fetch Table B.
- **Check**: Look for currencies with very low values (e.g., IDR - Indonesian Rupiah).
- **Expectation**: The value should NOT be displayed in scientific notation (e.g., `4.1E-5`). It must be a plain decimal string (e.g., `0.000041`).
- **Reasoning**: This ensures `bcmath` functions do not crash.

### 3. Business Logic Validation

**Scenario: Using Table C for Calculations**
- **Action**: Pass a Table C object to `CurrencyCalculator::convert()`.
- **Expectation**: The system must throw an `InvalidArgumentException` with a message stating that Table C cannot be used for average rate conversions.

### 4. Input Validation (Value Objects)

**Scenario: Invalid Date Format**
- **Action**: Use `getCurrencyTableForDate('A', 'invalid-date')`.
- **Expectation**: `DateValue` should throw an `InvalidArgumentException` before the request is even sent.

**Scenario: Invalid Currency Code**
- **Action**: Pass "USDD" (4 letters) to the calculator.
- **Expectation**: `CurrencyCode` should throw an `InvalidArgumentException`.

## Manual Test Script

You can use the `examples/basic_usage.php` file as a baseline for manual testing. 

```bash
# Run the example script
php examples/basic_usage.php
```

Verify that all outputs (PL and EN) are correct and that the values match the current data on the [NBP website](https://nbp.pl/statystyka-i-sprawozdawczosc/kursy/).
