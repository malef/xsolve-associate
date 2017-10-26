<?php

namespace Xsolve\Associate\Metadata;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

class MetadataWrapperProvider
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ClassMetadataWrapper[]
     */
    protected $classMetadataWrappers = [];

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $objects
     *
     * @return ClassMetadataWrapper|null
     */
    public function getClassMetadataWrapperByObjects(array $objects)
    {
        $classNames = array_unique(array_map(
            function ($object) { return get_class($object); },
            $objects
        ));

        $classMetadataWrappers = array_map(
            function (string $className) { return $this->getClassMetadataWrapperByClassName($className); },
            $classNames
        );

        // If metadata were not available for some objects.
        if (in_array(null, $classMetadataWrappers, true)) {
            return;
        }

        $classNames = array_unique(array_map(
            function (ClassMetadataWrapper $classMetadataWrapper) {
                return $classMetadataWrapper->getClassName();
            },
            $classMetadataWrappers
        ));

        if (1 === count($classNames)) {
            return reset($classMetadataWrappers);
        }

        $rootClassNames = array_unique(array_map(
            function (ClassMetadataWrapper $classMetadataWrapper) {
                return $classMetadataWrapper->getRootClassName();
            },
            $classMetadataWrappers
        ));

        if (1 === count($rootClassNames)) {
            return $this->getClassMetadataWrapperByClassName(
                reset($rootClassNames)
            );
        }
    }

    /**
     * @param string $className
     *
     * @return ClassMetadataWrapper|null
     */
    public function getClassMetadataWrapperByClassName(string $className)
    {
        if (!array_key_exists($className, $this->classMetadataWrappers)) {
            $classMetadata = $this->entityManager->getClassMetadata($className);

            if ($classMetadata instanceof ClassMetadata) {
                $this->classMetadataWrappers[$className] = new ClassMetadataWrapper(
                    $this,
                    $this->entityManager->getRepository($className),
                    $classMetadata
                );
            } else {
                $this->classMetadataWrappers[$className] = null;
            }
        }

        return $this->classMetadataWrappers[$className];
    }
}
