<?php

namespace Enzim\RestOrmBundle\ORM;

use Doctrine\Common\Persistence\ObjectRepository;

class RestObjectRepository implements ObjectRepository
{

    /** @var ResourceLoader */
    private $loader;

    /** @var string */
    private $entityName;

    /**
     * @param $loader
     * @param $entityName
     */
    function __construct(ResourceLoader $loader, $entityName)
    {
        $this->loader = $loader;
        $this->entityName = $entityName;
    }

    /**
     * Finds an object by its primary key / identifier.
     *
     * @param mixed $id The identifier.
     *
     * @return object The object.
     */
    public function find($id)
    {
        return $this->loader->loadById($id);
    }

    /**
     * Finds all objects in the repository.
     *
     * @return array The objects.
     */
    public function findAll()
    {
        $firstPage = $this->loader->loadById(null);
        $collection = new RestCollection($this->loader, $firstPage);

        return $collection; //->toArray();
    }

    /**
     * Finds objects by a set of criteria.
     *
     * Optionally sorting and limiting details can be passed. An implementation may throw
     * an UnexpectedValueException if certain values of the sorting or limiting details are
     * not supported.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array The objects.
     *
     * @throws \UnexpectedValueException
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $firstPage = $this->loader->load($criteria, $orderBy, $offset);
        $collection = new RestCollection($this->loader, $firstPage);

        if ($offset !== null && $limit !== null) {
            return new PagedResult($collection, $offset, $limit);
        }

        return $collection->toArray();
    }

    /**
     * Finds a single object by a set of criteria.
     *
     * @param array $criteria The criteria.
     *
     * @return object The object.
     */
    public function findOneBy(array $criteria)
    {
        // TODO: Implement findOneBy() method.
    }

    /**
     * Returns the class name of the object managed by the repository.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->entityName;
    }

}
