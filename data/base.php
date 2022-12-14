<?
namespace PackTheSettings\Data;

abstract class Base implements IBase
{
    protected $params = [];
    protected $errorText = '';

    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    public function getErrorText(): string
    {
        return $this->errorText;
    }

    public function getChangedValues(): array
    {
        $defaultValues = static::getDefaultValues();
        return array_filter(
                    $this->params,
                    function($value, $key) use($defaultValues) {
                        return isset($defaultValues[$key]) && ($value != $defaultValues[$key]);
                    },
                    ARRAY_FILTER_USE_BOTH
                );
    }

    public function getNormalizedValues(): array
    {
        return $this->getMainParams() + $this->getChangedValues() + static::getDefaultValues();
    }
}