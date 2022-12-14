<?
namespace PackTheSettings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\Base;
use \Bitrix\Main\Loader;

class PropertyEnum extends Base
{
    protected $ID = false;
    protected $value;
    protected $property;

    public function __construct(string $value, Property $property)
    {
        $this->value = $value;
        $this->property = $property;
    }

    public function getID(): int
    {
        return $this->ID;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getProperty(): Property
    {
        return $this->property;
    }

    public static function init(array $data)
    {
        if (!is_string($data['VALUE'])) return;

        $property = ClassName::getInstanceViaID(Property::class, $data['PROPERTY_CLASS'], $data['PROPERTY_ID']);
        if (!$property) return;

        $unit = new static($data['VALUE'], $property);
        return $unit->setParams($data);
    }

    public static function get(array $filter)
    {
        $ID = intval($filter['ID']);
        if ($ID < 1) return;

        Loader::includeModule('iblock');

        $data = \CIBlockPropertyEnum::GetByID($ID);
        if (!$data) return;

        if (isset($filter['PROPERTY_CLASS']))
            $data['PROPERTY_CLASS'] = $filter['PROPERTY_CLASS'];
            
        $unit = static::init($data);
        $unit->ID = $ID;
        return $unit;
    }

    public static function getDefaultValues(): array
    {
        return [
            'DEF' => '',
            'SORT' => 10,
            'XML_ID' => '',
            'TMP_ID' => ''
        ];
    }

    public function getMainParams(): array
    {
        return [
            'PROPERTY_ID' => $this->property->getID(),
            'VALUE' => $this->value,
        ];
    }

    public function save()
    {
        if (!$this->property->getID())
            throw new \Exception(
                        Loc::getMessage(
                            'ERROR_BAD_PROPERTY_ID_FOR_CREATING',
                            ['#CLASSNAME#' => get_class($this->property)]
                        )
                    );

        if ($this->ID) return $this->ID;

        Loader::includeModule('iblock');

        $property = new \CIBlockPropertyEnum;
        $this->ID = $property->Add($this->getNormalizedValues());
        if (!$this->ID) $this->errorText = Loc::geMessage('ERROR_PROPERTY_ENUM_UNIT_CREATING');

        return $this->ID;
    }
}