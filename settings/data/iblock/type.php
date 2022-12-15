<?
namespace PackTheSettings\Settings\Data\IBlock;

use \PackTheSettings\Data\IBlock\Type as DataType;

use \PackTheSettings\Settings\Printer;
use \PackTheSettings\Settings\Data\Base;

use \Bitrix\Main\Localization\Loc;

class Type extends Base
{
    const SETTINGS_CODE = 'IBlockTypes';

    public function __construct(DataType $typeID)
    {
        $this->data = $typeID;

        $code = strtoupper(md5($this->data->getCode()));
        $this->ID = sprintf('INFS_IB_TYPE_{%s}', $code);
        $this->langID = sprintf('IB_T_{LANG_%s_NAME}', $code);
    }

    public function getConstantValues(Printer $printer = null): array
    {
        return [
            $this->ID => $this->data->getCode()
        ];
    }

    public function getLangValues(Printer $printer = null): array
    {
        if (
            $this->isExcluded($printer)
            || $this->data->isDefault()
        ) return [];

        return [
            $this->langID => $this->data->getName()
        ];
    }

    public function getShortSettings(Printer $printer = null): array
    {
        if (
            $this->isExcluded($printer)
            || $this->data->isDefault()
        ) return [];

        return [
                'LANG_CODE' => $this->langID
            ] + $this->data->getChangedValues();
    }

    public function getComment(int $commentType, Printer $printer = null): string
    {
        if ($commentType == Printer::COMMENT_CONST_NAME) {
            return Loc::getMessage('IBLOCK_TYPE_CONST_NAME', ['#NAME#' => $this->data->getName()]);

        } elseif ($commentType == Printer::COMMENT_LANG_NAME) {
            return Loc::getMessage('IBLOCK_TYPE_LANG_NAME', ['#NAME#' => $this->data->getName()]);

        } elseif ($commentType == Printer::COMMENT_AT_CONSTANTS) {
            return Loc::getMessage('IBLOCK_TYPE_COMMENT_AT_CONSTANTS', ['#NAME#' => $this->data->getName()]);
            
        } elseif (!$this->isExcluded($printer) && !$this->data->isDefault()) {
            if ($commentType == Printer::COMMENT_AT_LANGS) {
                return Loc::getMessage('IBLOCK_TYPE_COMMENT_AT_LANGS', ['#NAME#' => $this->data->getName()]);

            } elseif ($commentType == Printer::COMMENT_AT_SETTINGS) {
                return Loc::getMessage('IBLOCK_TYPE_COMMENT_AT_SETTINGS', ['#NAME#' => $this->data->getName()]);
            }
        }
        return '';
    }
}