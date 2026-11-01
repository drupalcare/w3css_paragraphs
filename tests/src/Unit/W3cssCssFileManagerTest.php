<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Unit;

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssFileManager;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssFileManagerInterface;
use Drupal\w3css_paragraphs\Service\Css\W3cssCssValidator;
use Drupal\w3css_paragraphs\Service\Css\W3cssDynamicLibraryRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * File names, hash rule and library definitions, on a real temporary directory.
 */
#[CoversClass(W3cssCssFileManager::class)]
#[CoversClass(W3cssDynamicLibraryRegistrar::class)]
#[Group('w3css_paragraphs')]
final class W3cssCssFileManagerTest extends TestCase {

  private const KEY = 'paragraph-w3css-simple-42';

  private const CSS = "#a{height:1px;}\n";

  /**
   * The temporary directory standing in for the public files directory.
   */
  private string $root;

  /**
   * Makes saveData() fail.
   */
  private bool $failSave = FALSE;

  /**
   * Makes delete() fail.
   */
  private bool $failDelete = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->root = sys_get_temp_dir() . '/w3css-' . bin2hex(random_bytes(6));
    mkdir($this->root);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    foreach (glob($this->root . '/*') ?: [] as $path) {
      unlink($path);
    }
    rmdir($this->root);
    parent::tearDown();
  }

  /**
   * Builds a manager with a fresh per-request index.
   */
  private function manager(?LoggerInterface $logger = NULL): W3cssCssFileManager {
    $fileSystem = $this->createStub(FileSystemInterface::class);
    $path = fn (string $uri): string => $this->root . '/' . basename($uri);
    $fileSystem->method('prepareDirectory')->willReturn(TRUE);
    $fileSystem->method('realpath')->willReturnCallback(fn (): string => $this->root);
    $fileSystem->method('saveData')->willReturnCallback(function (string $data, string $uri) use ($path): string {
      if ($this->failSave) {
        throw new FileException('disk full');
      }
      file_put_contents($path($uri), $data);
      return $uri;
    });
    $fileSystem->method('delete')->willReturnCallback(function (string $uri) use ($path): bool {
      if ($this->failDelete) {
        throw new FileException('denied');
      }
      unlink($path($uri));
      return TRUE;
    });
    return new W3cssCssFileManager($fileSystem, new W3cssCssValidator(), $logger ?? $this->createStub(LoggerInterface::class));
  }

  /**
   * Lists the real files.
   *
   * @return list<string>
   *   The file names.
   */
  private function files(): array {
    return array_values(array_diff((array) scandir($this->root), ['.', '..']));
  }

  /**
   * Tests that a file name carries the key and a hash and nothing else.
   */
  public function testWriteNamesTheFileByHash(): void {
    $manager = $this->manager();
    $this->assertTrue($manager->write(self::KEY, self::CSS));
    $files = $this->files();
    $this->assertCount(1, $files);
    $this->assertMatchesRegularExpression('/^paragraph-w3css-simple-42\.[0-9a-f]{10}\.css$/D', $files[0]);
    $this->assertSame(self::CSS, file_get_contents($this->root . '/' . $files[0]));
    $this->assertSame(W3cssCssFileManagerInterface::DIRECTORY . '/' . $files[0], $manager->fileUri(self::KEY));
  }

  /**
   * Tests keys that could reach outside the directory.
   *
   * @dataProvider hostileKeys
   */
  #[DataProvider('hostileKeys')]
  public function testHostileKeysWriteNothing(string $key): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())->method('error');
    $this->assertFalse($this->manager($logger)->write($key, self::CSS));
    $this->assertSame([], $this->files());
  }

  /**
   * Gives hostile keys.
   *
   * @return array<string, array{string}>
   *   The keys.
   */
  public static function hostileKeys(): array {
    return [
      'traversal' => ['../../settings'],
      'nested traversal' => ['paragraph-a-1/../../../x'],
      'php' => ['paragraph-a-1.php'],
      'uppercase' => ['paragraph-A-1'],
      'other type' => ['user-a-1'],
      'zero id' => ['paragraph-a-0'],
      'newline' => ["paragraph-a-1\n"],
      'null byte' => ["paragraph-a-1\0"],
      'empty' => [''],
    ];
  }

  /**
   * Tests the hash rule: the file is used only for exactly its own CSS.
   */
  public function testMatchesOnlyTheSameCss(): void {
    $manager = $this->manager();
    $this->assertFalse($manager->matches(self::KEY, self::CSS), 'No file yet.');
    $manager->write(self::KEY, self::CSS);
    $this->assertTrue($manager->matches(self::KEY, self::CSS));
    // What a draft or a translation with other values would build.
    $this->assertFalse($manager->matches(self::KEY, "#a{height:2px;}\n"));
    $this->assertFalse($manager->matches('paragraph-w3css-simple-43', self::CSS));
    // A later request reads the same answer from the disk.
    $this->assertTrue($this->manager()->matches(self::KEY, self::CSS));
  }

  /**
   * Tests that the rule reads the disk, so a removed file does not match.
   */
  public function testMatchesRefusesMissingFile(): void {
    $this->manager()->write(self::KEY, self::CSS);
    unlink($this->root . '/' . $this->files()[0]);
    $this->assertFalse($this->manager()->matches(self::KEY, self::CSS));
  }

  /**
   * Tests that a new value replaces the old file.
   */
  public function testRewriteRemovesTheOldFile(): void {
    $manager = $this->manager();
    $manager->write(self::KEY, self::CSS);
    $old = $this->files()[0];
    $manager->write(self::KEY, "#a{height:2px;}\n");
    $this->assertCount(1, $this->files());
    $this->assertNotSame($old, $this->files()[0]);
    $this->assertFalse($manager->matches(self::KEY, self::CSS));
    $this->assertTrue($manager->matches(self::KEY, "#a{height:2px;}\n"));
  }

  /**
   * Tests that a failed write keeps the old file and does not match new CSS.
   */
  public function testFailedWriteLeavesNoFalseMatch(): void {
    $manager = $this->manager();
    $manager->write(self::KEY, self::CSS);
    $this->failSave = TRUE;
    $this->assertFalse($manager->write(self::KEY, "#a{height:2px;}\n"));
    $this->assertCount(1, $this->files());
    $this->assertFalse($manager->matches(self::KEY, "#a{height:2px;}\n"));
  }

  /**
   * Tests that a failed prune makes both CSS versions fall back to inline.
   */
  public function testFailedPruneMatchesNothing(): void {
    $manager = $this->manager();
    $manager->write(self::KEY, self::CSS);
    $this->failDelete = TRUE;
    $this->assertTrue($manager->write(self::KEY, "#a{height:2px;}\n"));
    $this->assertCount(2, $this->files());
    $this->assertFalse($manager->matches(self::KEY, self::CSS));
    $this->assertFalse($manager->matches(self::KEY, "#a{height:2px;}\n"));
    $this->assertFalse($this->manager()->matches(self::KEY, "#a{height:2px;}\n"));
  }

  /**
   * Tests that an id cannot match a longer id with the same start.
   */
  public function testIdsDoNotCollide(): void {
    $manager = $this->manager();
    $manager->write('paragraph-a-24', self::CSS);
    $manager->write('paragraph-a-240', self::CSS);
    $manager->delete('paragraph-a-24');
    $this->assertSame(['paragraph-a-240'], $this->manager()->listKeys());
  }

  /**
   * Tests that files this class did not name are never listed or removed.
   */
  public function testForeignFilesAreLeftAlone(): void {
    $foreign = ['notes.css', 'paragraph-a-1.css', 'paragraph-a-1.abcdefghij.css', '../x.css', 'evil.0123456789.css'];
    foreach ($foreign as $name) {
      file_put_contents($this->root . '/' . basename($name), 'x');
    }
    $count = count($this->files());
    $manager = $this->manager();
    $this->assertSame([], $manager->listKeys());
    $this->assertSame(0, $manager->deleteAll());
    $this->assertCount($count, $this->files());
  }

  /**
   * Tests delete and deleteAll.
   */
  public function testDelete(): void {
    $manager = $this->manager();
    $this->assertTrue($manager->delete(self::KEY), 'Missing counts as deleted.');
    $manager->write(self::KEY, self::CSS);
    $manager->write('node-w3css-paragraphs-content-7', self::CSS);
    $this->assertTrue($manager->delete(self::KEY));
    $this->assertSame('', $manager->fileUri(self::KEY));
    $this->assertSame(1, $manager->deleteAll());
    $this->assertSame([], $this->files());
  }

  /**
   * Tests a filesystem where realpath() cannot reach the directory.
   */
  public function testNoRealpathFindsNoFile(): void {
    $fileSystem = $this->createStub(FileSystemInterface::class);
    $fileSystem->method('realpath')->willReturn(FALSE);
    $manager = new W3cssCssFileManager($fileSystem, new W3cssCssValidator(), $this->createStub(LoggerInterface::class));
    $this->assertSame([], $manager->listKeys());
    $this->assertFalse($manager->matches(self::KEY, self::CSS));
  }

  /**
   * Tests the library definition: a stream URI, late in the theme group.
   */
  public function testLibraries(): void {
    $manager = $this->manager();
    $manager->write(self::KEY, self::CSS);
    $registrar = new W3cssDynamicLibraryRegistrar($manager);
    $this->assertSame('w3css_paragraphs/dynamic--' . self::KEY, $registrar->libraryId(self::KEY));
    $libraries = $registrar->buildLibraries();
    $this->assertSame(['dynamic--' . self::KEY], array_keys($libraries));
    $uri = W3cssCssFileManagerInterface::DIRECTORY . '/' . $this->files()[0];
    $this->assertSame(['weight' => 1000, 'minified' => TRUE], $libraries['dynamic--' . self::KEY]['css']['theme'][$uri]);
  }

}
