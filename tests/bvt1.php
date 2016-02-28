<?php
/**
 * Unit tests for bitvec
 * @author cpks
 */
require 'bitvec.php';
require 'tests/prime.php';

class bitvecTest extends PHPUnit_Framework_TestCase {
  static private $vec;

  public static function setUpBeforeClass() {
    self::$vec = new bitvec;
  }

  public function testcounts() {
    $this->assertEquals(0, count(self::$vec));
    self::$vec->setSize(33);
    $this->assertGreaterThan(32, self::$vec->getSize());
  }

  public function testSetAll() {
    self::$vec->setSize(32);
    self::$vec->setall();
    $lim = self::$vec->count();
    $overtop = $lim + 1;
    self::$vec->setSize(1024);
    $this->assertEquals(1, self::$vec[0]);
    $this->assertEquals(1, self::$vec[1]);
    $this->assertEquals(1, self::$vec[$lim - 1]);
    $this->assertEquals(0, self::$vec[$overtop]);
    self::$vec->setSize(32);
    $this->assertEquals(1, self::$vec[0]);
    $this->assertEquals(1, self::$vec[1]);
    $this->assertEquals(1, self::$vec[$lim - 1]);
  }
  /**
   * @expectedException RuntimeException
   */
  public function testNegIndex() {
    self::$vec[-1] = 1;
  }

  /**
   * @expectedException RuntimeException
   */
  public function testBigIndex() {
    self::$vec[10000] = 1;
  }

  public function testPrimeGeneration() {
    $e = new erato_prime(50); // use bitvec to generate primes using 3 sieve techniques
    $a = new atkin_prime(50);
    $s = new sundaram_prime(50);
    $this->assertTrue($e->same_as($a));
    $this->assertTrue($e->same_as($s));
  }
}
