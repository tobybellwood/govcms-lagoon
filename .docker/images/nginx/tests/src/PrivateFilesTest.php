<?php

namespace GovCMSTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Ensure that the private files are restricted.
 */
class PrivateFilesTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    // Make sure private files directory exists in the nginx container.
    shell_exec('docker compose exec nginx mkdir -p /app/web/sites/default/files/private');
    foreach (self::providerFileAccess() as $parts) {
      list($file, $path) = $parts;
      // Move out test files.
      shell_exec("docker cp $path/$file $(docker compose ps -q nginx):/app/web/sites/default/files/private/");
    }
  }

  /**
   * Return a list of files to test.
   *
   * @return array
   *    File list.
   */
  public static function providerFileAccess(): array {
    $path = dirname(__DIR__);
    return [
      ['autotest.jpg', "$path/resources/"],
      ['autotest.pdf', "$path/resources/"],
      ['autotest.rtf', "$path/resources/"],
    ];
  }

  /**
   * Ensure that private files are restricted.
   */
  #[DataProvider('providerFileAccess')]
  public function testFileAccess($file, $path) {
    $testPath = "/sites/default/files/private/$file";
    $headers = \get_curl_headers($testPath);
    $this->assertEquals(404, $headers['Status'], "[$testPath] is publicly accessible");
  }

}
