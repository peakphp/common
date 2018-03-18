<?php

declare(strict_types=1);

namespace Peak\Common;

use Peak\Common\Traits\ArrayMergeRecursiveDistinct;

use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use \Exception;
use \Closure;

/**
 * Collection object
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    use ArrayMergeRecursiveDistinct;

    /**
     * Collection items
     * @var array
     */
    protected $items = [];

    /**
     * Lock write
     * @var boolean
     */
    protected $read_only = false;

    /**
     * Create a new collection
     *
     * @param  array $items
     */
    public function __construct($items = null)
    {
        if (is_array($items)) {
            $this->items = $items;
        }
    }

    /**
     * Set read only on
     */
    public function readOnly(): void
    {
        $this->read_only = true;
    }

    /**
     * Check if its read only
     *
     * @return boolean
     */
    public function isReadOnly(): bool
    {
        return $this->read_only;
    }

    /**
     * Create a new instance of collection
     *
     * @param  array $items
     * @return Collection
     */
    public static function make($items = null): Collection
    {
        return new static($items);
    }

    /**
     * Use most of php built in array_ functions that accept an array as first arguments
     * Don't work with passed by reference array like array_push
     *
     * ex: $obj->array_keys() or $obj->keys()
     *
     * @param  string $func array_ func
     * @param  mixed  $argv
     * @return mixed
     * @throws Exception
     */
    public function __call($func, $argv)
    {
        if (is_callable('array_'.$func)) {
            return call_user_func('array_'.$func, $this->items, ...$argv);
        }
        if (!is_callable($func) || substr($func, 0, 6) !== 'array_') {
            throw new Exception(__CLASS__.': method ['.$func.'] is unknown');
        }
        return call_user_func($func, $this->items, ...$argv);
    }

    /**
     * Get an item by key
     *
     * @param  string $key
     * @return mixed
     */
    public function &__get($key)
    {
        return $this->items[$key];
    }

    /**
     * Assigns a value to the specified item
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value): void
    {
        if (!$this->isReadOnly()) {
            $this->items[$key] = $value;
        }
    }

    /**
     * Whether or not an item exists by key
     *
     * @param   string $key
     * @return  bool
     */
    public function __isset($key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * Unset an item by key
     *
     * @param string $key
     */
    public function __unset($key): void
    {
        if (!$this->isReadOnly()) {
            unset($this->items[$key]);
        }
    }

    /**
     * Assign a value to the specified offset
     *
     * @param  string $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        if ($this->isReadOnly()) {
            return;
        }

        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Whether an item exists
     *
     * @param  string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * Item to delete
     *
     * @param  string $offset
     */
    public function offsetUnset($offset)
    {
        if ($this->isReadOnly()) {
            return;
        }
        unset($this->items[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    /**
     * Push item to the end of collection
     *
     * @param mixed $item
     * @return $this
     */
    public function push($item): Collection
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * Count items
     *
     * @return integer
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Create iterator for $config
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Determine if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Empty the collection
     */
    public function strip(): void
    {
        $this->items = [];
    }

    /**
     * To array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * To simple object
     *
     * @return \stdClass
     */
    public function toObject(): \stdClass
    {
        return (object)$this->items;
    }

    /**
     * Json serialize
     *
     * @param  integer $options Bitmask (see php.net json_encode)
     * @param  integer $depth   Set the maximum depth. Must be greater than zero.
     * @return string
     */
    public function jsonSerialize(int $options = 0, int $depth = 512): string
    {
        return json_encode($this->items, $options, $depth);
    }

    /**
     * Array map
     *
     * @param  Closure $closure
     * @return $this
     */
    public function map(Closure $closure): Collection
    {
        $this->items = array_map($closure, $this->items);
        return $this;
    }

    /**
     * Merge two arrays recursively overwriting the keys in the first array
     * if such key already exists
     *
     * @param  array $a      Array to merge to the current collection
     * @param  array|null $b If specified, $b will be merge into $a and replace current collection
     * @return array
     */
    public function mergeRecursiveDistinct($a, $b = null, $get_result = false): ?array
    {
        if (!isset($b)) {
            $temp = $a;
            $a = $this->items;
            $b = $temp;
        }

        $items = $this->arrayMergeRecursiveDistinct($a, $b);

        if ($get_result) {
            return $items;
        }

        $this->items = $items;
        return null;
    }
}
