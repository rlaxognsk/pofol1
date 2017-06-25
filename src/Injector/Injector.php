<?php
namespace Pofol\Injector;

use Exception;
use ReflectionClass;
use ReflectionMethod;

class Injector
{
    protected static function createDependencyParams(array $dependencies)
    {
        $dependencyParams = [];

        for ($i = 0, $length = count($dependencies); $i < $length; $i++) {
            $dependency = $dependencies[$i]->getClass();

            if ($dependency === null) {
                break;
            }

            $dependencyClass = $dependency->name;

            $dependencyParams[] = new $dependencyClass(self::instance($dependencyClass));
        }

        return $dependencyParams;
    }

    public static function instance($className, ...$params)
    {
        $reflector = new ReflectionClass($className);

        if (!$reflector->isInstantiable()) {
            throw new Exception("{$className}는 인스턴스화 될 수 없습니다.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $className;
        }

        $dependencies = $constructor->getParameters();

        if (empty($dependencies)) {
            return new $className;
        }

        $dependencyParams = self::createDependencyParams($dependencies);

        return new $className(...$dependencyParams, ...$params);
    }

    public static function method($classNameOrObj, $methodName, ...$params)
    {
        if (!is_string($classNameOrObj) && !is_object($classNameOrObj)) {
            throw new Exception('클래스 이름이나 인스턴스를 넘겨야 합니다.');
        }

        $obj = self::instance($classNameOrObj);

        $reflector = new ReflectionMethod($classNameOrObj, $methodName);

        $dependencies = $reflector->getParameters();

        if (empty($dependencies)) {
            return $reflector->invokeArgs($obj, $params);
        }

        $dependencyParams = self::createDependencyParams($dependencies);

        return $reflector->invokeArgs($obj, array_merge($dependencyParams, $params));
    }
}
