# Job Board API Testing

This document provides instructions for testing the Job Board API with advanced filtering capabilities using Pest PHP.


## Running the Tests

To run all feature tests:

```bash
./vendor/bin/pest
```

To run just the job filter tests:

```bash
./vendor/bin/pest --filter=JobFilterTest
```

To run a specific test:

```bash
./vendor/bin/pest --filter="it can filter full-time senior PHP/JavaScript jobs with 3+ years experience that are remote"
```

## Understanding the Filter Tests

The tests in `JobFilterTest.php` verify that our complex filtering functionality works correctly. Each test:

1. Makes an API request with a specific filter query parameter
2. Verifies the response has a 200 status code
3. Checks that we received some data back
4. Verifies that each job in the response meets the filter criteria

### Test Cases

1. **Full-time PHP/JavaScript Senior Jobs**:  
   Tests filtering for full-time PHP or JavaScript jobs requiring 3+ years experience at a senior level that are remote.

2. **High-paying Jobs With Health Insurance**:  
   Tests filtering for jobs with salary over $100k that offer health insurance and require a Bachelor's degree or higher.

3. **Contract/Freelance Jobs With Flexible Hours**:  
   Tests filtering for contract or freelance positions with flexible schedules in major tech hubs.

4. **Entry/Junior Positions**:  
   Tests filtering for entry-level or junior positions with upcoming application deadlines.

5. **Multiple Programming Languages**:  
   Tests filtering for jobs requiring Python or JavaScript with 2-5 years of experience that are full-time or contract.

6. **Complex Nested Conditions**:  
   Tests a complex filter with nested conditions combining job type, programming languages, and experience requirements.

7. **Recently Published Senior Jobs**:  
   Tests filtering for recently published jobs with senior positions and competitive salary with health benefits.

8. **Skills-based Filtering**:  
   Tests filtering for full-time jobs that specifically mention Docker and AWS in the requirements.

## Notes on the Test Implementation

- The tests use the `RefreshDatabase` trait to ensure a clean test database for each test.
- Before each test, we seed the database with test data.
- We make assertions about the job properties and attributes to verify the filter worked correctly.
- For relationship-based filters (like languages, locations, categories), we make basic assertions but don't perform exhaustive validation.
- You may need to adjust the filter parameters based on your actual test data.
