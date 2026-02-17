<?php

namespace GovCMSTests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FilesCacheTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    shell_exec('docker compose exec nginx mkdir -p /app/sites/default/files');
    foreach (self::providerExpiredHeaderPath() as $parts) {
      list($file, $path) = $parts;
      shell_exec("docker cp $path/$file $(docker compose ps -q nginx):/app/web/sites/default/files/");
    }
  }

  /**
   * Return a list of files to test.
   *
   * @return array
   *   File list.
   */
  public static function providerExpiredHeaderPath(): array {
    $path = dirname(__DIR__);
    return [
      ['autotest.jpg', "$path/resources/", 'max-age=2628001'],
      ['autotest.pdf', "$path/resources/", 'max-age=1800'],
      ['autotest.rtf', "$path/resources/", 'max-age=2628001'],
    ];
  }

  /**
   * Ensure that expires headers are correctly set.
   */
  #[DataProvider('providerExpiredHeaderPath')]
  public function testExpiredHeaderPath($file, $path, $expected) {
    $path = "/sites/default/files/$file";
    $headers = \get_curl_headers($path);
    $this->assertEquals($expected, $headers['Cache-Control']);
  }

}
