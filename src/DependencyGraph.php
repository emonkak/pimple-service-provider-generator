<?php

namespace DependencyGraph;

/**
 * Represents dependency graph by any object.
 */
class DependencyGraph implements \IteratorAggregate
{
    /**
     * @var array of DependencyObject
     */
    private $objects = [];

    /**
     * @var callable
     */
    private $keySelector;

    /**
     * @param callable $keySelector
     */
    public function __construct(callable $keySelector)
    {
        $this->keySelector = $keySelector;
    }

    /**
     * @param mixed $object
     * @param array $dependencies
     * @retuen DependencyObject
     */
    public function addObject($object, array $dependencies)
    {
        $key = call_user_func($this->keySelector, $object);
        if (isset($this->objects[$key])) {
            $object = $this->objects[$key];
        } else {
            $object = new DependencyObject($key, $object);
            $this->objects[$key] = $object;
        }

        foreach ($dependencies as $dependency) {
            $depKey = call_user_func($this->keySelector, $dependency);
            if (isset($this->objects[$depKey])) {
                $depObject = $this->objects[$depKey];
            } else {
                $depObject = $this->objects[$depKey] = new DependencyObject($depKey, $dependency);
            }

            if ($object->isDependedBy($depObject)) {
                throw new \InvalidArgumentException(sprintf(
                    'Circular dependency detected between `%s` and `%s`',
                    call_user_func($this->keySelector, $depObject),
                    call_user_func($this->keySelector, $object)
                ));
            }

            $object->addDependency($depObject);
            $depObject->addPrecedency($object);
        }

        return $object;
    }

    /**
     * @return array of DependencyObject
     */
    public function getAllObjects()
    {
        return $this->objects;
    }

    /**
     * @return array of DependencyObject
     */
    public function getRootObjects()
    {
        $rootObjects = [];
        foreach ($this->objects as $object) {
            if (count($object->getPrecedencies()) === 0) {
                $rootObjects[] = $object;
            }
        }
        return $rootObjects;
    }

    /**
     * @see \IteratorAggregate
     * @return \Iterator
     */
    public function getIterator()
    {
        return new DependencyGraphIterator($this->getRootObjects());
    }
}
