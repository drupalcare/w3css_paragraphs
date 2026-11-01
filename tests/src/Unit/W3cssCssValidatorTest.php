<?php

declare(strict_types=1);

namespace Drupal\Tests\w3css_paragraphs\Unit;

use Drupal\w3css_paragraphs\Service\Css\W3cssCssValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Hostile values for every row of the plan's security table.
 */
#[CoversClass(W3cssCssValidator::class)]
#[Group('w3css_paragraphs')]
final class W3cssCssValidatorTest extends TestCase {

  /**
   * Tests that a valid color is rebuilt from its integers.
   */
  public function testValidColors(): void {
    $validator = new W3cssCssValidator();
    $this->assertSame('244,67,54', $validator->color('w3-244-67-54'));
    $this->assertSame('0,0,0', $validator->color('w3-0-0-0'));
    $this->assertSame('255,255,255', $validator->color('w3-255-255-255'));
    // Output comes from the integers and not from the string.
    $this->assertSame('7,8,9', $validator->color('w3-007-008-009'));
  }

  /**
   * Tests hostile colors.
   *
   * @dataProvider hostileColors
   */
  #[DataProvider('hostileColors')]
  public function testHostileColors(mixed $value): void {
    $this->assertNull((new W3cssCssValidator())->color($value));
    $this->assertNull((new W3cssCssValidator())->rgba($value, '50'));
  }

  /**
   * Gives hostile colors.
   *
   * @return array<string, array{mixed}>
   *   The values.
   */
  public static function hostileColors(): array {
    return [
      'empty' => [''],
      'null' => [NULL],
      'array' => [['w3-1-2-3']],
      'int' => [123],
      'bool' => [TRUE],
      'over 255' => ['w3-256-0-0'],
      'four digits' => ['w3-1000-0-0'],
      'negative' => ['w3--1-0-0'],
      'trailing newline' => ["w3-1-2-3\n"],
      'leading newline' => ["\nw3-1-2-3"],
      'trailing space' => ['w3-1-2-3 '],
      'declaration break' => ['w3-1-2-3;background:url(//evil.example)'],
      'rule break' => ['w3-1-2-3}body{display:none'],
      'style close' => ['w3-1-2-3</style><script>alert(1)</script>'],
      'comment' => ['w3-1-2-3/*'],
      'unicode digits' => ['w3-١-٢-٣'],
      'wrong prefix' => ['w4-1-2-3'],
      'named' => ['red'],
      'hex' => ['#ff0000'],
      'uppercase' => ['W3-1-2-3'],
      'three parts' => ['w3-1-2'],
      'five parts' => ['w3-1-2-3-4'],
      'null byte' => ["w3-1-2-3\0"],
    ];
  }

  /**
   * Tests valid opacity, including absent.
   */
  public function testValidOpacity(): void {
    $validator = new W3cssCssValidator();
    $this->assertSame('0.05', $validator->opacity('05'));
    $this->assertSame('0.01', $validator->opacity('01'));
    $this->assertSame('0.99', $validator->opacity('99'));
    $this->assertSame('1', $validator->opacity(NULL));
    $this->assertSame('1', $validator->opacity(''));
  }

  /**
   * Tests hostile opacity.
   *
   * @dataProvider hostileOpacities
   */
  #[DataProvider('hostileOpacities')]
  public function testHostileOpacity(mixed $value): void {
    $this->assertNull((new W3cssCssValidator())->opacity($value));
    $this->assertNull((new W3cssCssValidator())->rgba('w3-1-2-3', $value));
  }

  /**
   * Gives hostile opacities.
   *
   * @return array<string, array{mixed}>
   *   The values.
   */
  public static function hostileOpacities(): array {
    return [
      'zero' => ['00'],
      'hundred' => ['100'],
      'one digit' => ['5'],
      'int' => [50],
      'float' => [0.5],
      'array' => [['50']],
      'bool' => [TRUE],
      'trailing newline' => ["50\n"],
      'injection' => ['50);}body{display:none'],
      'negative' => ['-5'],
      'decimal' => ['.5'],
      'unicode digits' => ['٥٠'],
      'tag' => ['5<'],
    ];
  }

  /**
   * Tests valid pixel values.
   */
  public function testValidPixels(): void {
    $validator = new W3cssCssValidator();
    $this->assertSame(1, $validator->pixels(1));
    $this->assertSame(400, $validator->pixels('400'));
    $this->assertSame(10000, $validator->pixels(10000));
  }

  /**
   * Tests hostile pixel values.
   *
   * @dataProvider hostilePixels
   */
  #[DataProvider('hostilePixels')]
  public function testHostilePixels(mixed $value): void {
    $this->assertNull((new W3cssCssValidator())->pixels($value));
  }

  /**
   * Gives hostile pixel values.
   *
   * @return array<string, array{mixed}>
   *   The values.
   */
  public static function hostilePixels(): array {
    return [
      'zero' => [0],
      'zero string' => ['0'],
      'negative' => [-5],
      'negative string' => ['-5'],
      'over the limit' => [10001],
      'over the limit string' => ['10001'],
      'huge' => ['99999999999999999999'],
      'int max' => [PHP_INT_MAX],
      'float' => [5.5],
      'whole float' => [5.0],
      'bool' => [TRUE],
      'null' => [NULL],
      'array' => [[5]],
      'exponent' => ['1e3'],
      'hex' => ['0x10'],
      'unit' => ['12px'],
      'leading zero' => ['012'],
      'leading space' => [' 5'],
      'trailing newline' => ["5\n"],
      'injection' => ['5px;}body{display:none'],
      'expression' => ['calc(1px)'],
      'unicode digits' => ['٥'],
      'empty' => [''],
    ];
  }

  /**
   * Tests that a normal image URL is kept.
   */
  public function testValidImageUrl(): void {
    $url = '/sites/default/files/styles/wide/public/a%20b.jpg?itok=x-y_z';
    $this->assertSame("url('" . $url . "')", (new W3cssCssValidator())->imageUrl($url));
  }

  /**
   * Tests image URLs that must be refused outright.
   *
   * @dataProvider refusedImageUrls
   */
  #[DataProvider('refusedImageUrls')]
  public function testRefusedImageUrls(mixed $value): void {
    $this->assertNull((new W3cssCssValidator())->imageUrl($value));
  }

  /**
   * Gives image URLs that must be refused.
   *
   * @return array<string, array{mixed}>
   *   The values.
   */
  public static function refusedImageUrls(): array {
    return [
      'null' => [NULL],
      'array' => [['/a.jpg']],
      'empty' => [''],
      'relative' => ['a.jpg'],
      'scheme' => ['https://evil.example/a.jpg'],
      'javascript' => ['javascript:alert(1)'],
      'data' => ['data:image/svg+xml,x'],
      'protocol relative' => ['//evil.example/a.jpg'],
      'backslash host' => ['/\\evil.example/a.jpg'],
      'strip makes protocol relative' => ['/;/evil.example/a.jpg'],
      'strip makes backslash host' => ['/;\\evil.example/a.jpg'],
      'only a slash' => ['/'],
      'newline' => ["/a.jpg\n"],
      'newline in the middle' => ["/a\nb.jpg"],
      'carriage return' => ["/a\rb.jpg"],
      'form feed' => ["/a\fb.jpg"],
      'null byte' => ["/a\0b.jpg"],
      'invalid UTF-8' => ["/a\xFFb.jpg"],
      'too long' => ['/' . str_repeat('a', 2048)],
    ];
  }

  /**
   * Tests hostile characters that are escaped or stripped from an image URL.
   *
   * @dataProvider neutralizedImageUrls
   */
  #[DataProvider('neutralizedImageUrls')]
  public function testNeutralizedImageUrls(string $value, string $expected): void {
    $this->assertSame($expected, (new W3cssCssValidator())->imageUrl($value));
  }

  /**
   * Gives image URLs and their safe form.
   *
   * @return array<string, array{string, string}>
   *   The URL and the expected output.
   */
  public static function neutralizedImageUrls(): array {
    return [
      'single quote' => ["/a'b.jpg", "url('/a\\'b.jpg')"],
      'quote breakout' => ["/a');}body{display:none;}", "url('/a\\')bodydisplay:none')"],
      'double quote is inert' => ['/a"b.jpg', "url('/a\"b.jpg')"],
      'trailing backslash' => ['/a\\', "url('/a\\\\')"],
      'backslash then quote' => ["/a\\'", "url('/a\\\\\\'')"],
      'close tag' => ['/a</style><script>.jpg', "url('/a/stylescript.jpg')"],
      'braces and semicolon' => ['/a{b};c.jpg', "url('/abc.jpg')"],
    ];
  }

  /**
   * Tests that no image URL can end its string or open a tag.
   *
   * @dataProvider neutralizedImageUrls
   */
  #[DataProvider('neutralizedImageUrls')]
  public function testImageUrlStaysInsideItsString(string $value, string $expected): void {
    $url = (new W3cssCssValidator())->imageUrl($value);
    $this->assertSame($expected, $url);
    $inner = substr($url, 5, -2);
    $this->assertStringNotContainsString('<', $url);
    $this->assertStringNotContainsString('{', $url);
    $this->assertStringNotContainsString(';', $url);
    // Removing every escaped pair must leave no bare quote or backslash.
    $bare = preg_replace('/\\\\./s', '', $inner);
    $this->assertIsString($bare);
    $this->assertStringNotContainsString("'", $bare);
    $this->assertStringNotContainsString('\\', $bare);
  }

  /**
   * Tests slugs.
   *
   * @dataProvider hostileSlugs
   */
  #[DataProvider('hostileSlugs')]
  public function testHostileSlugs(mixed $value): void {
    $this->assertNull((new W3cssCssValidator())->slug($value));
  }

  /**
   * Gives values that are not machine names.
   *
   * @return array<string, array{mixed}>
   *   The values.
   */
  public static function hostileSlugs(): array {
    return [
      'traversal' => ['../../etc'],
      'slash' => ['a/b'],
      'hyphen' => ['a-b'],
      'space' => ['a b'],
      'uppercase' => ['Ab'],
      'selector' => ['a{}'],
      'tag' => ['<a'],
      'newline' => ["a\n"],
      'empty' => [''],
      'underscores only' => ['___'],
      'null' => [NULL],
      'array' => [['a']],
      'too long' => [str_repeat('a', 129)],
    ];
  }

  /**
   * Tests a valid slug.
   */
  public function testValidSlug(): void {
    $this->assertSame('w3css-3d-flip-box', (new W3cssCssValidator())->slug('w3css_3d_flip_box'));
  }

  /**
   * Tests hostile entity ids.
   *
   * @dataProvider hostileIds
   */
  #[DataProvider('hostileIds')]
  public function testHostileIds(mixed $value): void {
    $this->assertNull((new W3cssCssValidator())->entityId($value));
  }

  /**
   * Gives hostile entity ids.
   *
   * @return array<string, array{mixed}>
   *   The values.
   */
  public static function hostileIds(): array {
    return [
      'null' => [NULL],
      'zero' => [0],
      'zero string' => ['0'],
      'negative' => [-1],
      'float' => [1.5],
      'text' => ['abc'],
      'injection' => ['1{}'],
      'traversal' => ['1/../2'],
      'newline' => ["1\n"],
      'overflow' => ['99999999999999999999'],
    ];
  }

  /**
   * Tests UUIDs.
   */
  public function testUuid(): void {
    $validator = new W3cssCssValidator();
    $this->assertSame('123e4567e89b12d3a456426614174000', $validator->uuid('123e4567-e89b-12d3-a456-426614174000'));
    $this->assertNull($validator->uuid("123e4567-e89b-12d3-a456-426614174000\n"));
    $this->assertNull($validator->uuid('123e4567-e89b-12d3-a456-42661417400g'));
    $this->assertNull($validator->uuid('x{}'));
    $this->assertNull($validator->uuid(NULL));
  }

  /**
   * Tests storage keys, which name files.
   */
  public function testStorageKeys(): void {
    $validator = new W3cssCssValidator();
    $this->assertTrue($validator->isStorageKey('paragraph-w3css-simple-42'));
    $this->assertTrue($validator->isStorageKey('node-w3css-paragraphs-content-7'));
    $bad = [
      '',
      'paragraph-w3css-simple-0',
      'paragraph-w3css-simple-',
      'paragraph--1',
      'user-w3css-simple-1',
      '../paragraph-a-1',
      'paragraph-a-1/../../x',
      'paragraph-a-1.php',
      "paragraph-a-1\n",
      'paragraph-A-1',
      'paragraph-a{}-1',
      'paragraph-' . str_repeat('a', 160) . '-1',
    ];
    foreach ($bad as $key) {
      $this->assertFalse($validator->isStorageKey($key), var_export($key, TRUE));
    }
  }

  /**
   * Tests the sink guard.
   */
  public function testSafeCss(): void {
    $validator = new W3cssCssValidator();
    $this->assertTrue($validator->isSafeCss("#a{background-color:rgba(1,2,3,1);}\n"));
    $this->assertFalse($validator->isSafeCss('#a{}</style><script>alert(1)</script>'));
    $this->assertFalse($validator->isSafeCss('<'));
  }

}
