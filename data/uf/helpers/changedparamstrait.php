<?
namespace PackTheSettings\Data\UF\Helpers;

use PackTheSettings\Data\UF\Base as UFBase;

trait ChangedParamsTrait
{
    public function getChangedParams(): array
    {
        $result = parent::getChangedParams();
        if (!empty($this->params['SETTINGS'])) {
            $result['SETTINGS'] = array_filter(
                                        $this->params['SETTINGS'],
                                        function($value) { return !empty($value); }
                                    );
            if (empty($result['SETTINGS'])) unset($result['SETTINGS']);
        }

        $methodTypeOutData = 'setOutDataFor' . preg_replace('/[^a-z]+/', '', $this->params['USER_TYPE_ID']) . 'Type';
        if (method_exists($this, $methodTypeOutData)) $this->$methodTypeOutData($result);

        return $result;
    }
}