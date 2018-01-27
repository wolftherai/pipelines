<?php

/* this file is part of pipelines */

namespace Ktomk\Pipelines\Cli\Args;


use Iterator as PhpIterator;

class Iterator extends Args implements PhpIterator
{
    /**
     * @var Args
     */
    private $args;

    /**
     * @var int
     */
    private $index;

    /**
     * @var array
     */
    private $indexes;

    /**
     * Iterator constructor.
     * @param Args $args
     */
    public function __construct(Args $args)
    {
        $this->args = $args;
        $this->rewind();
    }

    /**
     * get next argument (look ahead)
     *
     * @return null|string next argument, null if there is no next argument
     */
    public function getNext()
    {
        return $this->getByIndex($this->index + 1);
    }

    /**
     * @param int $index
     * @return string|null argument string or null if not found
     */
    private function getByIndex($index)
    {

        $argsIndex = $this->getArgsIndex($index);
        if (null === $argsIndex) {
            return null;
        }

        if (!isset($this->args->arguments[$argsIndex])) {
            return null;
        }

        return $this->args->arguments[$argsIndex];
    }

    /**
     * @param int $index to retrieve index for
     *
     * @return int|null integer index of the argument, null if there is none
     */
    private function getArgsIndex($index)
    {
        if (!isset($this->indexes[$index])) {
            return null;
        }

        return $this->indexes[$index];
    }

    /* Iterator */

    /**
     * Return the current element
     *
     * @return string
     */
    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        return $this->getByIndex($this->index);
    }

    /**
     * Move forward to next option
     */
    public function next()
    {
        if (!$this->valid()) {
            return;
        }

        $this->index++;
    }

    /**
     * Return the key of the current option
     *
     * @return int index, zero based
     */
    public function key()
    {
        return $this->getArgsIndex($this->index);
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        $current = $this->getByIndex($this->index);

        if (null === $current) {
            return false;
        }

        return true;
    }

    /**
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->index = 0;
        $this->indexes = array_keys($this->args->arguments);
    }
}