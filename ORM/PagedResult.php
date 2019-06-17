<?php

namespace Enzim\RestOrmBundle\ORM;



class PagedResult implements \ArrayAccess, \IteratorAggregate
{
    /** @var RestCollection */
    private $collection;

    /** @var int */
    private $offset;

    /** @var int */
    private $limit;

    /** @var array */
    private $data;


    /**
     * @param RestCollection $collection
     * @param int $offset
     * @param int $limit
     */
    function __construct($collection, $offset, $limit)
    {
        $this->collection = $collection;
        $this->offset = $offset;
        $this->limit = $limit;
    }


    public function getTotal()
    {
        return $this->collection->count();
    }


    public function offsetExists($offset)
    {
        return isset( $this->getPageData()[$offset] );
    }


    public function offsetGet($offset)
    {
        return isset( $this->getPageData()[$offset] ) ? $this->getPageData()[$offset] : null;
    }


    public function offsetSet($offset, $value)
    {
        $this->getPageData()[ $offset ] = $value;
    }


    public function offsetUnset($offset)
    {
        unset( $this->getPageData()[ $offset ] );
    }


    public function getIterator()
    {
        return new \LimitIterator(
            $this->collection,
            $this->offset % ResourceLoader::PAGE_SIZE,
            $this->limit
        );
    }


    protected function getPageData()
    {
        if ($this->data === null) {
            $this->data = iterator_to_array( $this->getIterator() );
        }
        return $this->data;
    }

}