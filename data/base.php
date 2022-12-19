<?
namespace PackTheSettings\Data;

abstract class Base implements IBase
{
    protected $params = [];
    protected $errorText = '';

    public function setParams(array $params): IBase
    {
        $this->errorText = '';
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
                        return isset($defaultValues[$key]) && ($value != $defaultValues[$key]);
                    },
                    ARRAY_FILTER_USE_BOTH
                );
    }

    public function getNormalizedParams(): array
    {
        return $this->getMainParams() + $this->getChangedParams() + static::getDefaultParams();
    }
}