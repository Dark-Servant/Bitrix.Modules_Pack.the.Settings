<?
namespace PackTheSettings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\{
    IBase,
    Base as DataBase
};
use \Bitrix\Main\Loader;

class PropertyEnum extends DataBase
{
    protected $ID = false;
    protected $value;
    protected $property;

    public function __construct(string $value, Property $property)
    {
        $this->value = trim($value);
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

    public static function init(array $data): ?IBase
    {
        if (!is_string($data['VALUE'])) return null;

        $property = ClassName::getInstanceViaID(Property::class, $data['PROPERTY_CLASS'], $data['PROPERTY_ID']);
        if (!$property) return null;

        $unit = new static($data['VALUE'], $property);
        return $unit->setParams($data);
    }

    public static function get(array $filter): ?IBase
    {
        $ID = intval($filter['ID']);
        if ($ID < 1) return null;

        Loader::includeModule('iblock');

        $data = \CIBlockPropertyEnum::GetByID($ID);
        if (!$data) return null;

        if (isset($filter['PROPERTY_CLASS']))
            $data['PROPERTY_CLASS'] = $filter['PROPERTY_CLASS'];
            
        $unit = static::init($data);
        $unit->ID = $ID;
        return $unit;
    }

    public static function getDefaultParams(): array
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

    public function getNormalizedParams(): array
    {
        $result = parent::getNormalizedParams();
        if (empty($result['XML_ID'])) unset($result['XML_ID']);

        return $result;
    }

    public function save()
    {
        if (!$this->property->getID()) {
            $this->errorText = Loc::getMessage(
                                    'ERROR_BAD_PROPERTY_ID_FOR_CREATING',
                                    ['#CLASSNAME#' => get_class($this->property)]
                                );
            return 0;
        }

        if ($this->ID) return $this->ID;

        Loader::includeModule('iblock');

        $property = new \CIBlockPropertyEnum;
        $this->ID = $property->Add($this->getNormalizedParams());
        if (!$this->ID) $this->errorText = Loc::geMessage('ERROR_PROPERTY_ENUM_UNIT_CREATING');

        return $this->ID;
    }

    public static function createUniqueValues(array $units, Property $property): ?array
    {
        $resultIDs = [];
        $savedValues = [];
        foreach ($units as $unit) {
            if (!is_array($unit) || empty($unit['VALUE'])) continue;

            $correctedValue = trim($unit['VALUE']);
            $lowerCaseValue = strtolower($correctedValue);
            if (empty($lowerCaseValue) || in_array($lowerCaseValue, $savedValues))
                continue;

            $savedValues[] = $lowerCaseValue;
            $resultIDs[$correctedValue] = $classUnit = (new static($correctedValue, $property))->setParams($unit);

            $ID = $classUnit->save();
            if (!$ID) break;
        }
        return $resultIDs;
    }

    public static function remove(array $filter): bool
    {
        $data = static::get($filter);
        if (!$data) return false;

        Loader::includeModule('iblock');

        \CIBlockPropertyEnum::Delete($data->ID);
        return true;
    }
}