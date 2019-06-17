<?php


namespace Enzim\RestOrmBundle\ORM;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\ORM\ORMException;
use Enzim\RestOrmBundle\Serializer\JsonLdSerializer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

define('DEFAULT_SOURCE_PATH', __DIR__ . '/../../..');


class RestObjectManager implements ObjectManager
{

    /** @var string */
    private $entryPointUri;

    /** @var ApiRequestHelper */
    private $apiRequestHelper;

    /** @var string */
    private $baseUri;

    /** @var string */
    protected $namespace;

    /** @var MappingDriver */
    private $metadataDriver;

    /** @var JsonLdSerializer */
    private $serializer;

    /** @var array */
    private $classToUriMap;

    /** @var array */
    private $resourceLoaders;

    /** @var Cache */
    private $metadataCache;

    public function __construct(
        $entryPointUri,
        $apiRequestHelper,
        $namespace,
        $sourcePath = DEFAULT_SOURCE_PATH,
        $cache = null
    ) {
        $this->entryPointUri = $entryPointUri;
        $this->apiRequestHelper = $apiRequestHelper;
        $this->baseUri = $this->getBaseUri($this->entryPointUri);
        $this->namespace = $namespace;
        $this->metadataDriver = new SimplifiedYamlDriver([realpath($sourcePath) => $namespace]);
        $this->serializer = new JsonLdSerializer($this);
        $this->metadataCache = $cache ?: new ArrayCache();
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param $className
     *
     * @return ResourceLoader
     */
    public function getLoader($className)
    {
        if (!isset($this->resourceLoaders[$className])) {
            $this->resourceLoaders[$className] = $this->createLoader($className);
        }

        return $this->resourceLoaders[$className];
    }

    /**
     * Finds an object by its identifier.
     *
     * This is just a convenient shortcut for getRepository($className)->find($id).
     *
     * @param string $className The class name of the object to find.
     * @param mixed  $id        The identity of the object to find.
     *
     * @return object The found object.
     */
    public function find($className, $id)
    {
        return $this->getLoader($className)->loadById($id);
    }

    /**
     * Tells the ObjectManager to make an instance managed and persistent.
     *
     * The object will be entered into the database as a result of the flush operation.
     *
     * NOTE: The persist operation always considers objects that are not yet known to
     * this ObjectManager as NEW. Do not pass detached objects to the persist operation.
     *
     * @param object $object The instance to make managed and persistent.
     *
     * @return void
     */
    public function persist($object)
    {
        $class = new \ReflectionClass($object);
        $className = $class->getShortName();

        $id = $this->extractId($object);

        $result = $this->getLoader($className)->save($id, $object);

        if ($result !== null) {
            self::setId($class, $object, $this->extractId($result));
            self::copy($result, $object);
        }
    }

    /**
     * Removes an object instance.
     *
     * A removed object will be removed from the database as a result of the flush operation.
     *
     * @param object $object The object instance to remove.
     *
     * @return void
     */
    public function remove($object)
    {
        static::unsupported();
    }

    /**
     * Merges the state of a detached object into the persistence context
     * of this ObjectManager and returns the managed copy of the object.
     * The object passed to merge will not become associated/managed with this ObjectManager.
     *
     * @param object $object
     *
     * @return object
     */
    public function merge($object)
    {
        static::unsupported();
    }

    /**
     * Clears the ObjectManager. All objects that are currently managed
     * by this ObjectManager become detached.
     *
     * @param string|null $objectName if given, only objects of this type will get detached.
     *
     * @return void
     */
    public function clear($objectName = null)
    {
        static::unsupported();
    }

    /**
     * Detaches an object from the ObjectManager, causing a managed object to
     * become detached. Unflushed changes made to the object if any
     * (including removal of the object), will not be synchronized to the database.
     * Objects which previously referenced the detached object will continue to
     * reference it.
     *
     * @param object $object The object to detach.
     *
     * @return void
     */
    public function detach($object)
    {
        static::unsupported();
    }

    /**
     * Refreshes the persistent state of an object from the database,
     * overriding any local changes that have not yet been persisted.
     *
     * @param object $object The object to refresh.
     *
     * @return void
     */
    public function refresh($object)
    {
        // TODO: Implement refresh() method.
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * @return void
     */
    public function flush()
    {
        static::unsupported();
    }

    /**
     * Gets the repository for a class.
     *
     * @param string $className
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository($className)
    {
        return new RestObjectRepository($this->getLoader($className), $className);
    }

    /**
     * Returns the ClassMetadata descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)).
     *
     * @param string $className
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    public function getClassMetadata($className)
    {
        $metadata = $this->metadataCache->fetch($className);
        if ($metadata === false) {
            $metadata = new ClassMetadata($className);
            $this->metadataDriver->loadMetadataForClass($className, $metadata);
            $this->metadataCache->save($className, $metadata);
        }

        return $metadata;
    }

    /**
     * Gets the metadata factory used to gather the metadata of classes.
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        // TODO: Implement getMetadataFactory() method.
    }

    /**
     * Helper method to initialize a lazy loading proxy or persistent collection.
     *
     * This method is a no-op for other objects.
     *
     * @param object $obj
     *
     * @return void
     */
    public function initializeObject($obj)
    {
        // TODO: Implement initializeObject() method.
    }

    /**
     * Checks if the object is part of the current UnitOfWork and therefore managed.
     *
     * @param object $object
     *
     * @return bool
     */
    public function contains($object)
    {
        static::unsupported();
    }

    //--------------------------------------------------------------------------------

    protected function createLoader($className)
    {
        return new ResourceLoader(
            $this->apiRequestHelper,
            $this->baseUri,
            $this->getResourceUri($className),
            $this->serializer,
            $this->metadataCache
        );
    }

    /**
     * Get a resource's URI
     *
     * @param $className
     *
     * @return string
     * @throws NotFoundHttpException
     */
    protected function getResourceUri($className)
    {
        if ($this->classToUriMap === null) {
            $this->classToUriMap = $this->loadClassToUriMap();
        }

        if (substr($className, 0, strlen($this->namespace)) === $this->namespace) {
            $className = substr($className, strlen($this->namespace) + 1);
        }

        if (isset($this->classToUriMap)) {
            return @$this->classToUriMap[lcfirst($className)];
        }

        throw new NotFoundResourceException("API's resources were not loaded properly");
    }

    /**
     * Get the list of the API's resources paths
     *
     * @access protected
     * @return false|mixed
     */
    protected function loadClassToUriMap()
    {
        try {
            $response = $this->metadataCache->fetch($this->entryPointUri);
            if ($response === false || is_null($response)) {
                $response = $this->apiRequestHelper->makeApiRequest($this->entryPointUri, 'GET');
                $this->metadataCache->save($this->entryPointUri, $response);
            }

            return $response;
        } catch (RestError $e) {

        }
    }

    protected function extractId($object)
    {
        if (method_exists($object, 'getId')) {
            return $object->getId();
        }

        return null;
    }

    private function getBaseUri($entryPointUri)
    {
        $uriComponents = parse_url($entryPointUri);
        $baseUri = $uriComponents['scheme'] . '://' . $uriComponents['host'];
        if ($port = @$uriComponents['port']) {
            $baseUri .= ':' . $port;
        }

        return $baseUri;
    }

    private static function unsupported()
    {
        throw new ORMException('This operation is not supported in RestObjectManager');
    }

    private static function setId(\ReflectionClass $reflClass, $object, $id)
    {
        if ($reflClass->hasMethod('setId')) {
            $object->setId($id);
        } else {
            if ($reflClass->hasProperty('id')) {
                $reflProp = $reflClass->getProperty('id');
                $reflProp->setAccessible(true);
                $reflProp->setValue($object, $id);
            }
        }
    }

    private static function copy($source, $dest)
    {
        $reflClass = new \ReflectionClass($source);
        foreach ($reflClass->getMethods() as $method) {
            if (preg_match('/^get/', $method->getName())) {
                $setterName = 'set' . substr($method->getName(), 3);
                if (method_exists($dest, $setterName)) {
                    $setter = new \ReflectionMethod($dest, $setterName);
                    $setter->invoke($dest, $method->invoke($source));
                }
            }
        }
    }
}
