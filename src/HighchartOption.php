<?php
/**
 *
 * Copyright 2012-2012 Portugalmail Comunicações S.A (http://www.portugalmail.net/)
 *
 * See the enclosed file LICENCE for license information (GPLv3). If you
 * did not receive this file, see http://www.gnu.org/licenses/gpl-3.0.html.
 *
 * @author Gonçalo Queirós <mail@goncaloqueiros.net>
 */

namespace Ghunti\HighchartsPHP;

use ArrayAccess;

/**
 * @template-implements ArrayAccess<int, HighchartOption>
 */
class HighchartOption implements ArrayAccess
{
    /**
     * An array of HighchartOptions
     *
     * @var array<array-key, HighchartOption>
     */
    private array $items = [];

    /**
     * The option value
     *
     * @var mixed
     */
    private mixed $_value;

    /**
     * Clone HighchartOption object
     */
    public function __clone()
    {
        foreach ($this->items as $key => $value) {
            $this->items[$key] = clone $value;
        }
    }

    /**
     * The HighchartOption constructor
     *
     * @param mixed $value The option value
     */
    public function __construct(mixed $value = null)
    {
        if (is_string($value)) {
            //Avoid json-encode errors latter on
            if (function_exists('iconv')) {
                $this->_value = iconv(
                    mb_detect_encoding($value),
                    "UTF-8",
                    $value
                );
            } else {// fallback for servers that does not have iconv  
                $this->_value = mb_convert_encoding($value, "UTF-8", mb_detect_encoding($value));
            }
        } elseif (!is_array($value)) {
            $this->_value = $value;
        } else {
            foreach ($value as $key => $val) {
                $this->offsetSet($key, $val);
            }
        }
    }

    /**
     * Returns the value of the current option
     *
     * @return array<array-key, mixed>|string|null The option value
     */
    public function getValue(): mixed
    {
        if (isset($this->_value)) {
            //This is a final option
            return $this->_value;
        } elseif (!empty($this->items)) {
            //The option value is an array
            $result = [];
            foreach ($this->items as $key => $value) {
                $result[$key] = $value->getValue();
            }

            return $result;
        }

        return null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function __set(mixed $offset, mixed $value)
    {
        $this->offsetSet($offset, $value);
    }

    /**
     * @param mixed $offset
     *
     * @return false|self
     */
    public function __get(mixed $offset)
    {
        return $this->offsetGet($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = new self($value);
        } else {
            $this->items[$offset] = new self($value);
        }
        //If the option has at least one child, then it won't
        //have a final value
        unset($this->_value);
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return false|self
     */
    public function offsetGet(mixed $offset): false|self
    {
        //Unset the value, because we will always
        //have at least one child at the end of
        //this method
        unset($this->_value);
        if (is_null($offset)) {
            $this->items[] = new self();

            return end($this->items);
        }
        if (!isset($this->items[$offset])) {
            $this->items[$offset] = new self();
        }

        return $this->items[$offset];
    }
}
