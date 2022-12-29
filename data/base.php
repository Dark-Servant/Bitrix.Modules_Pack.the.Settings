<?
namespace PackTheSettings\Data;

use \PackTheSettings\Arguments\ClassName;

abstract class Base implements IBase
{
    protected $params = [];
    protected $errorText = '';
    protected $paramMethodData = [];

    public function getParamMethodNames(): array
    {
        return array_keys($this->paramMethodData);
    }

    public function setParams(array $params): IBase
    {
        $this->errorText = '';
        $this->paramMethodData = [];
        $this->params = $params;
        return $this;
    }

    public function getErrorText(): string
    {
        return $this->errorText;
    }

    public function getChangedParams(): array
    {
        $defaultValues = static::getDefaultParams();
        
        return array_filter(
                    $this->params,
                    function($value, $key) use($defaultValues) {
                        return isset($defaultValues[$key]) && !is_object($value) &&
                               !is_array($value) && ($value != $defaultValues[$key]);
                    },
                    ARRAY_FILTER_USE_BOTH
                );
    }

    public function getNormalizedParams(): array
    {
        return $this->getMainParams() + $this->getChangedParams() + static::getDefaultParams();
    }

    protected function initParamMethod(string $name, array $filter, string $className = self::class)
    {
        $realClassName = ClassName::getLastRelative(self::class, $className);
        $this->paramMethodData[$name]['callable'] = function() use($name, $realClassName, $filter) {
            if (!empty($this->paramMethodData[$name]['result']))
                return $this->paramMethodData[$name]['result'];
            
            $result = $realClassName::get($filter);
            if (!isset($result)) {
                $this->errorText = Loc::getMessage(
                                        'ERROR_BAD_LINKED_UNIT_FILTER',
                                        [
                                            '#NAME#' => $name,
                                            '#CLASSNAME#' => $realClassName,
                                            '#FILTER#' => PHP_EOL . print_r($filter, true),
                                        ]
                                    );
    
            } else {
                $this->paramMethodData[$name]['result'] = $result;
            }
            return $this->paramMethodData[$name]['result'];
        };
    }
    
    protected function getParamMethodData(string $methodName)
    {
        $name = strtolower($methodName);
        return is_callable($this->paramMethodData[$name]['callable'])
             ? $this->paramMethodData[$name]['callable']()
             : null;
    }

    public function __call(string $methodName, array $params = [])
    {
        if (preg_match('/^get(\w*)/i', $methodName, $methodNameParts))
            return $this->getParamMethodData($methodNameParts[1]);
    }
}