<?php

namespace Orm;

# ugly trick to resolve issue with "not normal" fuel's loader

if (!class_exists('Orm\Model')) {

    class Model implements \ArrayAccess, \Iterator
    {
        private $_data;

        private $_is_new;

        public function __construct($data = [], $new = true, $view = null, $cache = true)
        {
            $this->_data = $data;
            $this->_is_new = $new;
        }

        public function current()
        {
            return current($this->_data);
        }

        public function next()
        {
            return next($this->_data);
        }

        public function key()
        {
            return key($this->_data);
        }

        public function valid()
        {
            return $this->offsetExists(0);
        }

        public function rewind()
        {

        }

        public function offsetExists($offset)
        {
            return isset($this->_data[$offset]);
        }

        public function offsetGet($offset)
        {
            return $this->_data[$offset];
        }

        public function offsetSet($offset, $value)
        {
            $this->_data[$offset] = $value;
        }

        public function offsetUnset($offset)
        {
            unset($this->_data[$offset]);
        }

        public function to_array(): array
        {
            return $this->_data;
        }

        public function save($cascade = null, $use_transaction = false)
        {

        }

        public function __set($name, $value)
        {
            $this->offsetSet($name, $value);
        }

        public function __get($name)
        {
            return $this->offsetGet($name);
        }

        public function get_pk_assoc()
        {
            return [];
        }

        public function columns()
        {
            return [];
        }

        public function is_new()
        {
            return $this->_is_new;
        }
    }
}
