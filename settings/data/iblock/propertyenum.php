<?
namespace PackTheSettings\Settings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\IBlock\PropertyEnum as DataPropertyEnum;

use \PackTheSettings\Settings\Printer;
use \PackTheSettings\Settings\Data\Base;

class PropertyEnum extends Base
{
    const SETTINGS_CODE = false;

    protected $property;

    public function __construct(DataPropertyEnum $propertyEnum, $property = null)
    {
        $this->data = $propertyEnum;
        $this->property = ClassName::getInstance(Property::class, $property, $propertyEnum->getProperty());

        $this->ID = '{' . strtoupper(md5(sprintf('%s+%s', $this->data->getValue(), $this->data->getID()))) . '}';
        $this->langID = sprintf('%s_%s', $this->property->getLangID(), $this->ID);
    }

    public function getConstantValues(Printer $printer = null): array
    {
        return [];
    }

    public function getLangValues(Printer $printer = null): array
    {
        return [
            $this->langID => $this->data->getValue()
        ];
    }

    public function getShortSettings(Printer $printer = null): array
    {
        return [
            'LANG_CODE' => $printer ? $printer->replacingSpecialLangIDs($this->ID) : $this->ID,
        ] + $this->data->getChangedValues();
    }

    public function getComment(int $commentType, Printer $printer = null): string
    {
        return '';
    }

    public function isPreparedForPrinter(Printer $printer): bool
    {
        return false;
    }
}