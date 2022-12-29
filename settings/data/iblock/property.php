<?
namespace PackTheSettings\Settings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\IBlock\Property as DataProperty;

use \PackTheSettings\Settings\Printer;
use \PackTheSettings\Settings\Data\Base as SettingsBase;
use \PackTheSettings\Settings\Types\Base as BaseType;

use \Bitrix\Main\Localization\Loc;

class Property extends SettingsBase
{
    const SETTINGS_CODE = 'IBlockProperties';

    protected $iblock;
    protected $toIBlockPrint = true;
    protected $linkedIBlock = false;
    protected $enumClassName = PropertyEnum::class;

    public function __construct(DataProperty $property, $iblock = null)
    {
        $this->data = $property;
        $this->iblock = ClassName::getInstance(IBlock::class, $iblock, $property->getIBlock());
 
        $code = strtoupper(md5(sprintf('%s+%s', $this->data->getCode(), $this->data->getID())));
        $iblockID = preg_replace('/[\{\}]/', '', $this->iblock->getID());
        $this->ID = sprintf('INFS_IB_{%s_%s_CODE}', $iblockID, $code);
        $this->langID = sprintf('IB_PR_{LANG_%s_%s_NAME}', $iblockID, $code);

        if (($linkedIBlock = $this->data->getLinkedIBlock())) {
            $IBlockClassName = get_class($this->iblock);
            $this->linkedIBlock = new $IBlockClassName($linkedIBlock);
        }
    }

    public function getID()
    {
        return sprintf('%s_MDL', $this->ID);
    }

    public function setEnumClassName(string $enumClassName = PropertyEnum::class)
    {
        $this->enumClassName = ClassName::getLastRelative(PropertyEnum::class, $enumClassName);
    }

    public function getConstantValues(Printer $printer = null): array
    {
        $settingID = $this->iblock->getID();
        $ID = $this->ID;
        if ($printer) {
            $ID = $printer->replacingSpecialIDs($this->ID);
            $settingID = $printer->replacingSpecialIDs($this->iblock->getID());
        }

        return [
            $this->ID => $this->data->getCode(),
            $this->getID() => new BaseType(sprintf('%s . \'.\' . %s', $settingID, $ID))
        ];
    }

    public function getLangValues(Printer $printer = null): array
    {
        $result = [
            $this->langID => $this->data->getName()
        ];
        $enumClassName = $this->enumClassName;
        foreach ($this->data->ListValues() as $enumUnit) {
            $result += (new $enumClassName($enumUnit))->getLangValues($printer);
        }
        return $result;
    }

    public function getShortSettings(Printer $printer = null): array
    {
        $langCode = $this->langID;
        $iblockID = $this->iblock->getID();
        $linkedIBlockID = $this->linkedIBlock ? $this->linkedIBlock->getID() : false;
        if ($printer) {
            $langCode = $printer->replacingSpecialLangIDs($langCode);
            $iblockID = $printer->replacingSpecialIDs($iblockID);
            if ($linkedIBlockID)
                $linkedIBlockID = $printer->replacingSpecialIDs($linkedIBlockID);
        }

        $result = [
            'LANG_CODE' => $langCode,
            'IBLOCK_ID' => $iblockID
        ] + $this->data->getChangedParams();

        if ($result['PROPERTY_TYPE'] == 'L') {
            $enumClassName = $this->enumClassName;
            foreach ($this->data->ListValues() as $enumUnit) {
                $result['LIST_VALUES'][] = (new $enumClassName($enumUnit, $this))->getShortSettings($printer);
            }

        } elseif ($linkedIBlockID) {
            $result['LINK_IBLOCK_ID'] = $linkedIBlockID;
        }

        return $result;
    }

    public function getComment(int $commentType, Printer $printer = null): string
    {
        $params = [
            '#NAME#' => $this->data->getName(),
            '#IBLOCK_NAME#' => $this->data->getIBlock()->getName()
        ];
        if ($commentType == Printer::COMMENT_CONST_NAME) {
            return Loc::getMessage('IB_PROPERTY_CONST_NAME', $params);
            
        } elseif ($commentType == Printer::COMMENT_LANG_NAME) {
            return Loc::getMessage('IB_PROPERTY_LANG_NAME', $params);

        } elseif ($commentType == Printer::COMMENT_AT_CONSTANTS) {
            return Loc::getMessage('IB_PROPERTY_COMMENT_AT_CONSTANTS', $params);
            
        } elseif ($commentType == Printer::COMMENT_AT_LANGS) {
            return Loc::getMessage('IB_PROPERTY_COMMENT_AT_LANGS', $params);

        } elseif ($commentType == Printer::COMMENT_AT_SETTINGS) {
            return Loc::getMessage('IB_PROPERTY_COMMENT_AT_SETTINGS', $params);
        }
        return '';
    }

    public function setIBlockPrinting(bool $toPrint = true): Property
    {
        $this->toIBlockPrint = $toPrint;
        return $this;
    }

    public function isPreparedForPrinter(Printer $printer): bool
    {
        if ($this->isExcluded($printer)) return false;

        if ($this->toIBlockPrint) $printer->addData($this->iblock->setPropertyPrinting(false));
        if ($this->linkedIBlock) $printer->addData($this->linkedIBlock);
        return true;
    }
}