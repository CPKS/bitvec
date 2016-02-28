<?php
/******************************************************
 * Prime number checking and generation
 * @version 2.0
 * Adapted from:  Tzuly <tzulac@gmail.com> v1.0
 * @author  cpks
 * @license Public Domain
 * @package prime
 */

/**
 * Set of prime numbers
 *
 * Outputs:
 * - return whether a number is prime;
 * - construct a list of prime numbers;
 * - return biggest prime smaller than a number.
 * 
 *************
 * Use:      *
 *************
 * - for finding if a number is prime: prime::is_prime($number);
 * - for biggest prime number smaller than x:
 *          prime::smallerPrime($x);
 * - for list of prime numbers smaller than x: 
 *     $p = new prime($x);
 *   OR, using sieve of Eratosthenes:
 *     $e = new erato_prime($x);
 *   OR, using Atkin's sieve (a little slower):
 *     $a = new atkin_prime($x);
 *   OR, using sieve of Sundaram (uses half as much memory):
 *     $s = new sundaram_prime($x);
 * @package prime
 * @todo add an iterator to access the list and make primelist member private
 */
class prime {
  /*
   * @var int[] array contains list of prime numbers
   */
  public $primelist = array();

  /**
   * return whether a number is prime
   * 
   * @param int $number
   * @return boolean
   * @throws InvalidArgumentException
   */
  static public function is_prime($number) {
    if ($number < 2)
      throw new InvalidArgumentException('Prime numbers start at 2.');
    $number = (int)$number;
    for ($i = 2; $i < floor(sqrt($number) + 1); ++$i) {
      if (!($number % $i))
        return FALSE;
    }
    return TRUE;
  }
  /**
   * calculate biggest prime number smaller than $number
   * 
   * @param int $number upper bound
   * @return int
   * @throws InvalidArgumentException
   */
  static public function smallerPrime($number) {
    if ($number <= 2)
      throw new InvalidArgumentException('2 is smallest prime number');

    for ($i = $number - 1; $i > 1; --$i)
      if (self::is_prime($i)) return $i;
  }

  /**
   * generate list of prime numbers, computed by calculating if every number
   * from list is prime
   * 
   * @param int $number upper bound
   * @throws InvalidArgumentException
   */
  private function generate($number) {
    $this->primelist[] = 2;
    $this->primelist[] = 3;
    for ($i = 5; $i < $number; $i += 2) { // (BUG: was originally =+2)
      if (self::is_prime($i)) $this->primelist[] = $i;
    }
  }

  /**
   * Construct as a list of primes up to number supplied
   *
   * Use the generate method for the class
   * @param int $number upper bound
   * @throws InvalidArgumentException if upper bound < 3
   */
  public function __construct($number) {
    if ($number <= 2)
      throw new InvalidArgumentException('2 is smallest prime number');
    $this->generate($number);
  }

  /**
   * Compare with another instance
   * @param prime $other
   * @return bool TRUE if same
   */
  public function same_as(prime $other) {
    return $this->primelist == $other->primelist;
  }

  /**
   * Dump to stdout for debug purposes
   */
  public function dump() {
    echo implode(',', $this->primelist) . PHP_EOL;
  }
}

require_once 'bitvec.php';
/**
 * Override generation to use the Eratosthenes sieve algorithm
 * @package prime
 */
class erato_prime extends prime {
  /**
   * generate list of prime numbers, computed with Eratosthenes sieve algorithm
   * 
   * @param int $number
   */
  private function generate($number) {
    $erat = new bitvec($number);
    $erat->setall();

    for ($i = 2; $i < $number; ++$i) {
      if ($erat[$i]) {
        for ($j = 2; $j * $i < $number; ++$j) $erat[$i * $j] = 0;
      }
    }
    for ($i = 2; $i < $number; ++$i) {
      if ($erat[$i]) $this->primelist[] = $i;
    }
  }
}

/**
 * Override generation to use the Atkin sieve algorithm
 * @package prime
 */
class atkin_prime extends prime {
  /**
   * generate list of prime numbers, computed with atkin sieve algorithm
   * 
   * @param int $number
   */
  private function generate($number) {
    $atkin = new bitvec($number);
    for ($i = 1; $i < sqrt($number); ++$i) {
      for ($j = 1; $j < sqrt($number); ++$j) {
        $x = 4 * $i * $i + $j * $j;
        if ($x < $number && ($x % 12 == 1 || $x % 12 == 5))
          $atkin[$x] = 1;

        $x = 3 * $i * $i + $j * $j;
        if ($x < $number && $x % 12 == 7)
          $atkin[$x] = 1;

        $x = 3 * $i * $i - $j * $j;
        if ($i > $j && $x < $number && $x % 12 == 11)
          $atkin[$x] = 1;
      }
    }
    for ($i = 5; $i < sqrt($number); ++$i) {
      $x = $i * $i;
      $j = 1;
      while ($j * $x < $number) {
        $atkin[$j * $x] = 0;
        ++$j;
      }
    }
    $atkin[2] = $atkin[3] = 1;
    for ($i = 2; $i < $number; ++$i) {
      if ($atkin[$i]) $this->primelist[] = $i;
    }
  }
}

/**
 * Override generation to use the Sundaram sieve algorithm
 * @package prime
 */
class sundaram_prime extends prime {
  /**
   * generate list of prime numbers, computed with Sundaram sieve algorithm
   * 
   * @param int $number
   */
  private function generate($number) {
    $number >>= 1;
    $sunda = new bitvec($number);
    $sunda->setall();
    for ($i = 1; $i < $number; ++$i) {
      $denominator = ($i << 1) + 1;
      $maxVal = ($number - $i) / $denominator;
      for ($j = $i; $j < $maxVal; ++$j) $sunda[$i + $j * $denominator] = 0;
    }
    $this->primelist[] = 2;
    for ($i = 1; $i < $number; ++$i) if ($sunda[$i]) $this->primelist[] = ($i << 1) + 1;
  }
}
