<?php


namespace Enzim\RestOrmBundle\Serializer;

use Enzim\RestOrmBundle\ORM\RestObjectManager;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;


class JsonLdNormalizer extends PropertyNormalizer
{

    private static $internalTypes = [
        'hydra:Collection',
        'ConstraintViolationList',
        'Error',
    ];

    /** @var RestObjectManager */
    private $objectManager;

    /** @var string */
    private $namespace;

    /** @var LazyLoadingValueHolderFactory */
    private $proxyFactory;

    /** @var CamelCaseToSnakeCaseNameConverter */
    private $camelCaseNormalizer;

    public function __construct(RestObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->namespace = $objectManager->getNamespace();
        $this->proxyFactory = new LazyLoadingValueHolderFactory();
        $this->camelCaseNormalizer = new CamelCaseToSnakeCaseNameConverter();
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $class = $class ?: $this->getType($data);

        if ($class == 'Enzim\\RestOrmBundle\\Hydra\\Collection') {
            $normalizer = $this;
            $data['hydra:member'] = array_map(function ($item) use ($normalizer) {
                return $normalizer->denormalize($item, null);
            }, $data['hydra:member']);
        } else {
            if (strpos($class, $this->namespace . '\\') === 0) {
                $metadata = $this->objectManager->getClassMetadata($class);
                foreach ($data as $key => $value) {
                    if ($metadata->isSingleValuedAssociation($key)) {
                        $targetClass = $metadata->getAssociationTargetClass($key);
                        $resolver = $this->createObjectResolver($targetClass);
                        $data[$key] = $resolver($value);
                    } else {
                        if ($metadata->isCollectionValuedAssociation($key) && is_array($value)) {
                            $targetClass = $metadata->getAssociationTargetClass($key);
                            $resolver = $this->createObjectResolver($targetClass);
                            $data[$key] = array_map($resolver, $value);
                        }
                    }
                }
            }
        }

        $data = $this->prepareForDenormalization($data);

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeTest($propertyName)
    {
        if (null === $this->attributes || \in_array($propertyName, $this->attributes)) {
            $lcPropertyName = lcfirst($propertyName);
            $snakeCasedName = '';

            $len = \strlen($lcPropertyName);
            for ($i = 0; $i < $len; ++$i) {
                if (ctype_upper($lcPropertyName[$i])) {
                    $snakeCasedName .= '_'.strtolower($lcPropertyName[$i]);
                } else {
                    $snakeCasedName .= strtolower($lcPropertyName[$i]);
                }
            }

            return $snakeCasedName;
        }

        return $propertyName;
    }

    protected function createObjectResolver($targetClass)
    {
        $normalizer = $this;

        return function ($item) use ($normalizer, $targetClass) {
            return is_array($item) ?
                $normalizer->denormalize($item, null) :
                $normalizer->createProxy($targetClass, $item);
        };
    }

    protected function prepareForDenormalization($data)
    {
        if (isset($data['@id']) && !isset($data['id'])) {
            $segments = explode('/', $data['@id']);
            $data['id'] = end($segments);
        }

        if ($this->isInternalType($data['@type'])) {
            $result = [];
            $keyFilter = [$this, 'stripHydraPrefix'];
            array_walk($data, function (&$value, $key) use (&$result, $keyFilter) {
                $result[call_user_func($keyFilter, $key)] = $value;
            });
            $data = $result;
        }

        return $data;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        $type = $type ?: $this->getType($data);

        return parent::supportsDenormalization($data, $type, $format);
    }

    protected function getType($data)
    {
        if (!is_array($data) || !isset($data['@type'])) {
            throw new \RuntimeException('Unable to get target type');
        }

        $type = $data['@type'];
        if ($this->isInternalType($type)) {
            return 'Enzim\\RestOrmBundle\\Hydra\\' . $this->stripHydraPrefix($type);
        } else {
            return $this->getClassFQN($type);
        }
    }

    protected function isInternalType($type)
    {
        return in_array($type, self::$internalTypes);
    }

    protected function getClassFQN($class)
    {
        if (strpos($class, $this->namespace . '\\') === 0) {
            return $class;
        }

        return $this->namespace . '\\' . $class;
    }

    /**
     * @param $targetClass
     * @param $id
     *
     * @return \ProxyManager\Proxy\VirtualProxyInterface
     */
    public function createProxy($targetClass, $id)
    {
        $targetClass = $this->getClassFQN($targetClass);

        $resourceLoader = $this->objectManager->getLoader($targetClass);

        $proxyLoader = function (
            & $wrappedObject,
            LazyLoadingInterface $proxy,
            $method,
            array $parameters,
            & $initializer
        )
        use ($resourceLoader, $targetClass, $id) {
            $wrappedObject = $resourceLoader->loadByPath($id);
            $initializer = null;

            return true;
        };

        return $this->proxyFactory->createProxy($targetClass, $proxyLoader);
    }

    //Made public for PHP 5.3 Compatibility
    public function stripHydraPrefix($string)
    {
        if (preg_match('/hydra:(.+)/', $string, $matches)) {
            return $matches[1];
        }

        return $string;
    }
}
