<?

class PackTheSettings
{
    public static function checkBaseClass(array $nameParts)
    {
        $classNameParts = $nameParts;
        for ($partSize = count($classNameParts); $partSize > 1; --$partSize) {
            $classNameParts = array_slice($classNameParts, 0, -1);
            $baseClassNamePath = __DIR__ . '/' . implode('/', $classNameParts) . '/base.php';
            if (!file_exists($baseClassNamePath)) continue;
            
            require_once $baseClassNamePath;
            
            $baseClassName = self::class . '\\' . implode('\\', $classNameParts) . '\\Base';
            $nameSpaceName = self::class . '\\' . implode('\\', array_slice($nameParts, 0, -1));
            $className = current(array_slice($nameParts, -1));
            $classData = <<<BASE_CLASS
            namespace {$nameSpaceName};
            
            use {$baseClassName} as BaseClass;
            
            class {$className} extends BaseClass
            {}
            BASE_CLASS;

            eval($classData);
            break;
        }
    }

    public static function prepare(string $className)
    {
        $nameParts = explode('\\', strtolower($className));
        if ((count($nameParts) < 2) || ($nameParts[0] != strtolower(self::class)))
            return;

        $nameParts = array_slice($nameParts, 1);
        $fileName = __DIR__ . '/' . implode('/', $nameParts) . '.php';
        if (!file_exists($fileName)) return self::checkBaseClass($nameParts);

        require_once $fileName;
    }
}

\spl_autoload_register([PackTheSettings::class, 'prepare']);