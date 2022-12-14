<?
namespace PackTheSettings\Arguments;

use \Bitrix\Main\Localization\Loc;

abstract class ClassName
{

    protected static function throwExceptionClassNotExisting(string $className)
    {
        if (class_exists($className)) return;

        throw new \Exception(Loc::getMessage('ERROR_CLASS_EXISTING', ['#CLASSNAME#' => $mainClassName]));
    }

    protected static function throwExceptionClassNotRelative(string $mainClassName, string $subClassName)
    {
        static::throwExceptionClassNotExisting($subClassName);

        if (($subClassName != $mainClassName) && !is_subclass_of($subClassName, $mainClassName))
            throw new \Exception(
                        Loc::getMessage(
                            'ERROR_SUBCLASS_BAD_PARENT',
                            [
                                '#SUBCLASSNAME#' => $subClassName,
                                '#CLASSNAME#' => $mainClassName
                            ]
                        )
                    );
    }

    public static function getLastRelative(string $mainClassName, string $subClassName = null)
    {
        static::throwExceptionClassNotExisting($mainClassName);

        if (empty($subClassName)) return $mainClassName;
        
        static::throwExceptionClassNotRelative($mainClassName, $subClassName);

        return $subClassName;
    }

    public static function getInstance(string $mainClassName, $subClassName = null)
    {
        static::throwExceptionClassNotExisting($mainClassName);

        if (is_null($subClassName)) {
            $className = $mainClassName;

        } elseif (is_object($subClassName)) {
            static::throwExceptionClassNotRelative($mainClassName, get_class($subClassName));
            return $subClassName;

        } elseif (is_string($subClassName)) {
            static::throwExceptionClassNotRelative($mainClassName, $subClassName);
            $className = $subClassName;

        } else {
            throw new \Exception(Loc::getMessage('ERROR_BAD_SECOND_CLASS'));
        }

        return new $className(...array_slice(func_get_args(), 2));
    }

    public static function getInstanceVia(string $mainClassName, $subClassName, string $fieldName = null, $value = null)
    {
        static::throwExceptionClassNotExisting($mainClassName);

        if (is_object($subClassName) && ($subClassName instanceof $mainClassName)) {
            return $subClassName;
        
        } elseif (isset($fieldName) && isset($value)) {
            if (!empty($subClassName)) {
                static::throwExceptionClassNotRelative($mainClassName, $subClassName);
                $mainClassName = $subClassName;
            }

            return $mainClassName::get([$fieldName => $value]);
        }
    }

    public static function __callStatic(string $method, array $params = [])
    {
        if (preg_match('/^getinstancevia(\w+)/i', $method, $methodParts))
            return static::getInstanceVia($params[0], $params[1], $methodParts[1], $params[2]);

        throw new \Exception(Loc::getMessage('ERROR_BAD_METHOD_NAME', ['#NAME#' => $method]));
    }
}