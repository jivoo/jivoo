<?php
namespace Jivoo\Core;

class UnicodeTest extends \Jivoo\Test {
  public function testIsUpper() {
    $this->assertTrue(Unicode::isUpper('A'));
    $this->assertTrue(Unicode::isUpper('M'));
    $this->assertTrue(Unicode::isUpper('Z'));
    $this->assertTrue(Unicode::isUpper('Å'));
    $this->assertTrue(Unicode::isUpper('Ð'));
    $this->assertFalse(Unicode::isUpper('a'));
    $this->assertFalse(Unicode::isUpper('m'));
    $this->assertFalse(Unicode::isUpper('z'));
    $this->assertFalse(Unicode::isUpper('å'));
    $this->assertFalse(Unicode::isUpper('ð'));
  }
  
  public function testIsLower() {
    $this->assertFalse(Unicode::isLower('A'));
    $this->assertFalse(Unicode::isLower('M'));
    $this->assertFalse(Unicode::isLower('Z'));
    $this->assertFalse(Unicode::isLower('Å'));
    $this->assertFalse(Unicode::isLower('Ð'));
    $this->assertTrue(Unicode::isLower('a'));
    $this->assertTrue(Unicode::isLower('m'));
    $this->assertTrue(Unicode::isLower('z'));
    $this->assertTrue(Unicode::isLower('å'));
    $this->assertTrue(Unicode::isLower('ð'));
  }
  
  public function testLength() {
    $this->assertEquals(0, Unicode::length(''));
    $this->assertEquals(1, Unicode::length('a'));
    $this->assertEquals(1, Unicode::length('å'));
    $this->assertEquals(1, Unicode::length('♥'));
    $this->assertEquals(2, Unicode::length('♥♥'));
    $this->assertEquals(3, Unicode::length('♥a♥'));
  }
  
  public function testSlice() {
    $this->assertEquals('', Unicode::slice('', 0));
    $this->assertEquals('abcde', Unicode::slice('abcde', 0));
    $this->assertEquals('a', Unicode::slice('abcde', 0, 1));
    $this->assertEquals('ab', Unicode::slice('abcde', 0, 2));
    $this->assertEquals('bc', Unicode::slice('abcde', 1, 2));
    $this->assertEquals('abcd', Unicode::slice('abcde', 0, -1));
    $this->assertEquals('abc', Unicode::slice('abcde', 0, -2));
    $this->assertEquals('bc', Unicode::slice('abcde', 1, -2));
    $this->assertEquals('e', Unicode::slice('abcde', -1));
    $this->assertEquals('de', Unicode::slice('abcde', -2));
    $this->assertEquals('d', Unicode::slice('abcde', -2, -1));

    $this->assertEquals('☢☣☯♥☺', Unicode::slice('☢☣☯♥☺', 0));
    $this->assertEquals('☢', Unicode::slice('☢☣☯♥☺', 0, 1));
    $this->assertEquals('☢☣', Unicode::slice('☢☣☯♥☺', 0, 2));
    $this->assertEquals('☣☯', Unicode::slice('☢☣☯♥☺', 1, 2));
    $this->assertEquals('☢☣☯♥', Unicode::slice('☢☣☯♥☺', 0, -1));
    $this->assertEquals('☢☣☯', Unicode::slice('☢☣☯♥☺', 0, -2));
    $this->assertEquals('☣☯', Unicode::slice('☢☣☯♥☺', 1, -2));
    $this->assertEquals('☺', Unicode::slice('☢☣☯♥☺', -1));
    $this->assertEquals('♥☺', Unicode::slice('☢☣☯♥☺', -2));
    $this->assertEquals('♥', Unicode::slice('☢☣☯♥☺', -2, -1));
  }
}