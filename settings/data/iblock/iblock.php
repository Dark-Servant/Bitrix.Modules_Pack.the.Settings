<?
namespace PackTheSettings\Settings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\IBlock\IBlock as DataIBlock;

use \PackTheSettings\Settings\Printer;
use \PackTheSettings\Settings\Data\Base as SettingsBase;

use \Bitrix\Main\Localization\Loc;

class IBlock extends SettingsBase
{
    const SETTINGS_CODE = 'IBlocks';

    protected $typeID;
    protected $toPropertyPrint = true;
    protected $propertyClassName = Property::class;

    public function __construct(DataIBlock $iblock, $typeID = null)
    {
        $this->data = $iblock;
        $this->typeID = ClassName::getInstance(Type::class, $typeID, $iblock->getTypeID());

        $code = strtoupper(md5(sprintf('%s+%s', $this->data->getCode(), $this->data->getID())));
        $this->ID = sprintf('INFS_IBLOCK_{%s_CODE}', $code);
        $this->langID = sprintf('IBLOCK_{LANG_%s_NAME}', $code);

    }

    public function setPropertyClassName(string $propertyClassName = Property::class)
    {
        $this->propertyClassName = ClassName::getLastRelative(Property::class, $propertyClassName);
    }

    public function getConstantValues(Printer $printer = null): array
    {
        return [
            $this->ID => $this->isExcluded($printer)
                       ? $this->data->getID()
                       : $this->data->getCode()
        ];
    }

    public function getLangValues(Printer $printer = null): array
    {
        if ($this->isExcluded($printer)) return [];

        return [
            $this->langID => $this->data->getName()
        ];
    }

    public function getShortSettings(Printer $printer = null): array
    {
        if ($this->isExcluded($printer)) return [];

        $langCode = $this->langID;
        $iblockTypeID = $this->typeID->getID();
        if ($printer) {
            $langCode = $printer->replacingSpecialLangIDs($langCode);
            $iblockTypeID = $printer->replacingSpecialIDs($iblockTypeID);
        }

        return [
                'LANG_CODE' => $langCode,
                'IBLOCK_TYPE_ID' => $iblockTypeID,
            ] + $this->data->getChangedParams();
    }

    public function getComment(int $commentType, Printer $printer = null): string
    {
        if ($commentType == Printer::COMMENT_CONST_NAME) {
            return Loc::getMessage('IBLOCK_CONST_NAME', ['#NAME#' => $this->data->getName()]);
            
        } elseif ($commentType == Printer::COMMENT_LANG_NAME) {
            return Loc::getMessage('IBLOCK_LANG_NAME', ['#NAME#' => $this->data->getName()]);

        } elseif ($commentType == Printer::COMMENT_AT_CONSTANTS) {
            if ($this->isExcluded($printer)) {
                return Loc::getMessage('IBLOCK_ID_COMMENT_AT_CONSTANTS', ['#NAME#' => $this->data->getName()]);

            } else {
                return Loc::getMessage('IBLOCK_CODE_COMMENT_AT_CONSTANTS', ['#NAME#' => $this->data->getName()]);
            }
            
        } elseif (!$this->isExcluded($printer)) {
            if ($commentType == Printer::COMMENT_AT_LANGS) {
                return Loc::getMessage('IBLOCK_COMMENT_AT_LANGS', ['#NAME#' => $this->data->getName()]);

            } elseif ($commentType == Printer::COMMENT_AT_SETTINGS) {
                return Loc::getMessage('IBLOCK_COMMENT_AT_SETTINGS', ['#NAME#' => $this->data->getName()]);
            }
        }
        return '';
    }

    public function setPropertyPrinting(bool $toPrint = true): IBlock
    {
        $this->toPropertyPrint = $toPrint;
        return $this;
    }

    public function isPreparedForPrinter(Printer $printer): bool
    {
        $printer->addData($this->typeID);
        return true;
    }

    public function finishPrinting(Printer $printer)
    {
        if (!$this->toPropertyPrint) return;

        $propertyClassName = $this->propertyClassName;
        foreach ($this->data->getProperties() as $property) {
            $printer->addData((new $propertyClassName($property, $this))->setIBlockPrinting(false));
        }
    }
}