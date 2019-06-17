<?php

namespace Enzim\RestOrmBundle\ORM;

use Enzim\RestOrmBundle\Hydra\Collection;


class RestCollection implements \Iterator, \Countable
{

    /** @var ResourceLoader */
    private $resourceLoader;

    /** @var Collection */
    private $startPage;

    /** @var Collection */
    private $currentPage;

    /** @var integer */
    private $index;

    /** @var integer */
    private $pageNum;


    /**
     * @param ResourceLoader $resourceLoader
     * @param Collection     $startPage
     */
    public function __construct(ResourceLoader $resourceLoader, Collection $startPage)
    {
        $this->resourceLoader = $resourceLoader;
        $this->startPage = $startPage;
        $this->currentPage = $startPage;
        $this->index = 0;
        $this->pageNum = 0;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        $items = $this->currentPage->getMember();

        return $items[$this->index];
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->index++;
        if ($this->index >= count($this->currentPage->getMember())) {
            $query = $this->currentPage->getNextPage();
            if ($query !== null) {
                $this->currentPage = $this->resourceLoader->loadByPath($query);
                $this->index = 0;
                $this->pageNum++;
            }
        }
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return ($this->pageNum * $this->currentPage->getItemsPerPage()) + $this->index;
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        return 0 <= $this->index && $this->index < count($this->currentPage->getMember());
    }


    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->currentPage = $this->startPage;
        $this->index = 0;
        $this->pageNum = 0;
    }


    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return $this->startPage->getTotalItems();
    }


    /**
     * @return array
     */
    public function toArray()
    {
        $clone = new self($this->resourceLoader, $this->startPage);
        $array = [];
        foreach ($clone as $item) {
            $array[] = $item;
        }

        return $array;
    }

}