<?php

namespace Enzim\RestOrmBundle\Hydra;


class Collection
{
    /** @var string */
    private $id;

    /** @var integer */
    private $totalItems;

    /** @var integer */
    private $itemsPerPage;

    /** @var string */
    private $previousPage;

    /** @var string */
    private $nextPage;

    /** @var string */
    private $firstPage;

    /** @var string */
    private $lastPage;

    /** @var array */
    private $member;


    public static function create(array $members, $totalItems, $itemsPerPage, $previousPage, $nextPage, $firstPage, $lastPage)
    {
        $page = new self();
        $page->member = $members;
        $page->totalItems = $totalItems;
        $page->itemsPerPage = $itemsPerPage;
        $page->previousPage = $previousPage;
        $page->nextPage = $nextPage;
        $page->firstPage = $firstPage;
        $page->lastPage = $lastPage;
        return $page;
    }


    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }

    /**
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * @return string
     */
    public function getPreviousPage()
    {
        return $this->previousPage;
    }

    /**
     * @return string
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }

    /**
     * @return string
     */
    public function getFirstPage()
    {
        return $this->firstPage;
    }

    /**
     * @return string
     */
    public function getLastPage()
    {
        return $this->lastPage;
    }

    /**
     * @return array
     */
    public function getMember()
    {
        return $this->member;
    }

}