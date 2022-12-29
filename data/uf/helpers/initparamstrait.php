<?
namespace PackTheSettings\Data\UF\Helpers;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\{
    IBase,
    Vote\Base as VoteData,
    IBlock\IBlock
};
use \Bitrix\Main\Localization\Loc;

trait InitParamsTrait
{
    protected $VoteDataClassName = VoteData::class;
    protected $IBlockClassName = IBlock::class;

    public function setVoteDataClassName(string $className = VoteData::class)
    {
        $this->VoteDataClassName = ClassName::getLastRelative(VoteData::class, $className);
    }

    public function setIBlockClassName(string $className = IBlock::class)
    {
        $this->IBlockClassName = ClassName::getLastRelative(IBlock::class, $className);
    }

    protected function prepareParamsForVoteType()
    {
        if (empty($this->params['SETTINGS']['CHANNEL_ID'])) return;

        $this->initParamMethod('votechannel', ['ID' => $this->params['SETTINGS']['CHANNEL_ID']], $this->VoteDataClassName);
    }

    protected function prepareParamsForIBlockElementType()
    {
        if (empty($this->params['SETTINGS']['IBLOCK_ID'])) return;

        $this->initParamMethod('iblock', ['ID' => $this->params['SETTINGS']['IBLOCK_ID']], $this->IBlockClassName);
    }

    protected function prepareParamsForIBlockSectionType()
    {
        $this->prepareParamsForIBlockElementType();
    }

    protected function prepareParamsForCRMType()
    {
        $this->params['SETTINGS'] = is_array($this->params['SETTINGS'])
                                  ? array_filter(
                                        $this->params['SETTINGS'],
                                        function($value) { return $value != 'N'; }
                                    )
                                  : [];
    }

    public function setParams(array $params): IBase
    {
        parent::setParams($params);

        if (isset($params['TYPE']) && empty($params['USER_TYPE_ID']))
            $params['USER_TYPE_ID'] = $params['TYPE'];

        $params['USER_TYPE_ID'] = trim($params['USER_TYPE_ID']);
        if (empty($params['USER_TYPE_ID'])) {
            $this->errorText = Loc::getMessage('ERROR_EMPTY_USER_FIELD_TYPE');
            return $this;
        }

        $methodTypeChecker = 'prepareParamsFor' . preg_replace('/[^a-z]+/', '', $params['USER_TYPE_ID']) . 'Type';
        if (method_exists($this, $methodTypeChecker)) $this->$methodTypeChecker();

        return $this;
    }
}