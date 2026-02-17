# Nginx Tests

This directory contains PHPUnit tests for validating the nginx configuration, including security headers, bot blocking, and JavaScript SBOM functionality.

## Running Tests

### Standard Tests

To run the standard nginx tests:

```bash
ahoy test-nginx
```

This will run all tests including:
- Block tests (bot blocking, WordPress path blocking)
- Frame options tests
- Robots.txt and X-Robots-Tag tests
- Security headers tests
- File caching tests
- Private files access tests
- JavaScript SBOM tests (automatically configured)

### JavaScript SBOM Tests

The JavaScript SBOM tests verify the Lua-based functionality for:
- Detecting JavaScript sources in HTML responses
- Body filtering and content replacement
- Timer-based queue processing
- SBOM file creation

#### Automated Configuration (Default)

**The JS SBOM tests are fully automated and require no manual setup.** 

Simply run:

```bash
ahoy test-nginx
```

The `JsSbomTest` class automatically:
1. Creates a temporary docker-compose override file with required environment variables
2. Restarts nginx with the SBOM configuration (`JS_SBOM_ENABLED=true`, `JS_SBOM_FILE_LOCATION=/tmp/js-sbom.json`, etc.)
3. Runs all tests
4. Restores nginx to its original state after tests complete

#### Manual Configuration (Optional)

If you prefer to manually configure the environment or test with custom parameters, you can use the provided docker-compose override file:

```bash
# Start nginx with custom SBOM test configuration
docker-compose -f docker-compose.yml -f .docker/images/nginx/tests/docker-compose.sbom-test.yml up -d nginx

# Run the tests
ahoy test-nginx
```

#### Environment Variables

The following environment variables control the JS SBOM functionality:

- **JS_SBOM_ENABLED**: Set to `true` to enable SBOM detection
- **JS_SBOM_FILE_LOCATION**: Path to write SBOM JSON file (e.g., `/tmp/js-sbom.json`)
- **JS_SBOM_FIND**: Lua pattern to find in HTML responses (for body filtering). Note: This is a Lua pattern, not a literal string. Special characters (`. % + - * ? [ ] ^ $`) need to be escaped with `%` for literal matching.
- **JS_SBOM_REPLACE**: Replacement text for the find pattern
- **JS_SBOM_API_ENDPOINT**: Alternative to file output; HTTP endpoint to POST SBOM data

#### Specific Test Cases

The `JsSbomTest` class automatically configures the nginx environment and includes:

1. **testBodyFilteringReplacement**: Verifies that content replacement works when `JS_SBOM_FIND` and `JS_SBOM_REPLACE` are set
2. **testJavaScriptSourceDetection**: Verifies that script tags are detected in HTML responses
3. **testNonHtmlResponsesSkipped**: Ensures non-HTML responses are not processed
4. **testSbomFileCreation**: Verifies the SBOM file is created with valid JSON (requires 20+ second wait for timer)

All environment variables are configured automatically by the test class - no manual setup required.

#### Test Timeouts

Note that `testSbomFileCreation` includes a 20-second sleep to wait for the timer-based queue processor (which runs every 15 seconds). This test may take longer to complete.

## Test Structure

Tests are organized by functionality:

- `BlocksTest.php`: Bot blocking and malicious path blocking
- `FilesCacheTest.php`: File caching and expiration headers
- `FrameOptionsTest.php`: X-Frame-Options header
- `JsSbomTest.php`: JavaScript SBOM detection and body filtering
- `PrivateFilesTest.php`: Private file access restrictions
- `RobotsTagTest.php`: X-Robots-Tag header behavior
- `RobotsTxtTest.php`: robots.txt response
- `SecurityHeadersTest.php`: Security headers

## Adding New Tests

1. Create a new test class in `src/` extending `PHPUnit\Framework\TestCase`
2. Use the helper functions from `bootstrap.php`:
   - `get_curl_headers($path, $opts)`: Get response headers
   - `curl_get_content($path, $opts)`: Get response body
   - `is_debug()`: Check if running in debug mode
3. Run tests with `ahoy test-nginx`

## Debug Mode

Run tests with `--debug` flag to see detailed curl output:

```bash
.docker/images/nginx/tests/vendor/bin/phpunit -c .docker/images/nginx/tests/phpunit.xml --debug
```
