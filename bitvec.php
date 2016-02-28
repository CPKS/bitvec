<?php
/**
 * Vector of bits (array of 0/1 values)
 *
 * Implemented using SplFixedArray, for memory economy
 * @package storage
 * @author CPKS
 * @version 1.0
 * @license Public Domain
 */

/**
 * Iterator over bit vector
 *
 * Required to implement traversability for bitvec
 * @package storage
 */
class bitvecIterator implements \SeekableIterator, \ArrayAccess {
  /**
   * @var bitvec the bitvec we're iterating over
   */
  private $storage;
  /**
   * @var int the current location in the vector
   */
  private $pos;

  /**
   * Initialize on a bitvec instance
   * @param bitvec $bv instance to iterate over
   */
  public function __construct(bitvec $bv) {
    $this->storage = $bv;
    $this->pos = 0;
  }

  /**
   * Rewind the iterator
   */
  public function rewind() { $this->pos = 0; }

  /**
   * Advance the iterator
   */
  public function next() { ++$this->pos; }

  /**
   * Check iterator is valid
   * @return bool TRUE if so
   */
  public function valid() { return $this->pos < $this->storage->count(); }

  /**
   * Get iterator position
   * @return int
   */
  public function key() { return $this->pos; }

  /**
   * Get current value
   * @return int
   */
  public function current() { return $this->storage->offsetGet($this->pos); }

  /**
   * Seek to a given position
   * @param int $position
   * @throws \OutOfBoundsException if $position is not seekable
   */
  public function seek($position) {
    if ($position < 0 || $position >= $this->storage->count())
      throw new \OutOfBoundsException("Seek to illegal index $position");
    $this->pos = $position;
  }

  /**
   * Return whether offset exists
   *
   * Implements \ArrayAccess
   * @param int $offset
   * @return bool
   */
  public function offsetExists($offset) { return $this->storage->offsetExists($offset); }

  /**
   * Return value at specified offset
   *
   * Implements \ArrayAccess
   * @param int $offset
   * @return int the value, 0 or 1
   */
  public function offsetGet($offset) { return $this->storage->offsetGet($offset); }

  /**
   * Set value at offset
   *
   * Implements \ArrayAccess
   * @param int $offset
   * @param int $value must be 0 or 1
   */
  public function offsetSet($offset, $value) { $this->storage->offsetSet($offset, $value); }

  /**
   * Unset value (i.e. zero it) at specified offset
   *
   * Implements \ArrayAccess but with a twist: this does not do an unset in the
   * PHP sense! Rather, it unsets the bit.
   * @param int $offset
   */
  public function offsetUnset($offset) { $this->storage->offsetUnset($offset); }
}

/**
 * Vector of bits (0/1 values)
 *
 * Implemented using the compact SplFixedArray object, to minimize memory use.
 * Useful for arrays of boolean or flag values, as used e.g. in "sieve"
 * techniques for generating prime numbers.
 * @package storage
 */
class bitvec implements \ArrayAccess, \Countable, \IteratorAggregate {
  /**
   * Internal storage
   * @var \SplFixedArray
   */
  private $bits;
  /**
   * Mask differentiates bits for calculating array index from shift value
   * @var int
   */
  private $mask;
  /**
   * @var int shift factor to convert bit index to integer index
   */
  private $shf;

  /**
   * Internal set size
   *
   * Does not handle resizing
   * @param int $size no. of bits to contain
   * @return size of the integer array - used for zeroing it
   */
  private function set_size($size) {
    $bsize = ($size + $this->mask) >> $this->shf;
    $this->bits = new \SplFixedArray($bsize);
    return $bsize;
  }

  /**
   * Constructor may set array size
   *
   * Works out how many bits per element in $this->bits
   *
   * If size non-zero, initializes all bits zero
   * @param int $size no. of bits to contain
   */
  public function __construct($size = 0) {
    switch (\PHP_INT_SIZE) {
      case 2 : // 16-bit
        $this->mask = 0xF;
        $this->shf = 4;
      break; // 16-bit
      case 4 : // 32-bit
        $this->mask = 0x1F;
        $this->shf = 5;
      break;
      case 8 : // 64-bit
        $this->mask = 0x3F;
        $this->shf = 6;
      break;
      case 16: // 128-bit
        $this->mask = 0x7F;
        $this->shf = 7;
      break;
    }
    if ($size) { // set all bits zero
      for ($i = $this->set_size($size); --$i >= 0; ) $this->bits[$i] = 0;
    }
 }

  /**
   * Set size
   *
   * Preserves any existing contents where possible; initializes new bits zero
   * @param int $size
   * @return int new size (may be >$size)
   */
  public function setSize($size) {
    if (empty($this->bits)) {
      $this->set_size($size);
      return;
    }
    $oldsize = $this->bits->getSize();
    $bitsave = $this->bits;
    $this->set_size($size);
    $newsize = $this->bits->getSize();
    if ($oldsize === $newsize) {
      $this->bits = $bitsave;
      return;
    }
    $amount_to_copy = $newsize < $oldsize ? $newsize : $oldsize;
    for ($i = 0; $i < $amount_to_copy; ++$i) $this->bits[$i] = $bitsave[$i];
    for ( ; $i < $newsize; ++$i) $this->bits[$i] = 0;
    return $newsize;
  }

  /**
   * Set all bits zero
   */
  public function clear() {
    for ($i = $this->bits->count(); --$i >= 0; ) $this->bits[$i] = 0;
  }
  /**
   * Set all bits 1
   */
  public function setall() {
    for ($i = $this->bits->count(); --$i >= 0; ) $this->bits[$i] = -1; // assume 2's complement
  }

  /**
   * Get size (== count)
   * @return int
   */
  public function getSize() {
    if (!$this->bits) return 0;
    return $this->bits->count() << $this->shf;
  }

  /**
   * Get count (implement Countable)
   *
   * Returns the size; bit vectors fill their predetermined size
   * @return int
   */
  public function count() {
    return $this->getSize();
  }

  /**
   * Return whether an offset exists
   *
   * It will exist if the offset is < size
   * @param int $index offset to check
   * @return bool TRUE iff the offset exists
   */
  public function offsetExists($index) {
    return $index < $this->getSize();
  }

  /**
   * Unset value at specified index
   *
   * Implement ArrayAccess, but with a twist: we cannot unset the value,
   * so we simply zero it.
   * @param int $index the vector offset
   */
  public function offsetUnset($index) {
    $this->bits[$index >> $this->shf] &= ~(1 << ($index & $this->mask));
  }

  /**
   * Set value at specified index
   *
   * Implement ArrayAccess
   * @param int $index the vector offset
   * @param mixed $newval evaluated as TRUE (1) or FALSE (0)
   */
  public function offsetSet($index, $newval) {
    if (!$newval)
      $this->offsetUnset($index);
    else {
      $this->bits[$index >> $this->shf] |= 1 << ($index & $this->mask);
    }
  }

  /**
   * Get value at specified index
   *
   * Implement ArrayAccess
   * @param int $index the vector offset
   * @return int 0 or 1 value at that offset
   */
  public function offsetGet($index) {
    return ($this->bits[$index >> $this->shf] & 1 << ($index & $this->mask)) ? 1 : 0;
  }

  /**
   * Get iterator
   *
   * Implement IteratorAggregate
   * @return bitvecIterator
   */
  public function getIterator() {
    return new bitvecIterator($this);
  }
}
