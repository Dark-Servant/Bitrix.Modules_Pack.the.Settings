<?
namespace PackTheSettings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\{
    IBase,
    Base as DataBase
};
use \Bitrix\Main\{
    Loader,
    Localization\Loc
};

class IBlock extends DataBase
{
    protected $ID = 0;
    protected $name;
    protected $code;
    protected $typeID;

    public function __construct(string $name, string $code, Type $typeID)
    {
        /**
         * В коде для инфоблоков есть своя проверка на то,
         * указано ли название. Свою проверку писать не надо
         */
        $this->name = trim($name);
        $this->code = $code ?: md5($name);
        $this->typeID = $typeID;
    }

    public function getID(): int
    {
        return $this->ID;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getTypeID(): Type
    {
        return $this->typeID;
    }

    public static function init(array $data): ?IBase
    {
        if (!is_string($data['NAME'])) return null;

        $typeID = ClassName::getInstanceViaID(Type::class, $data['TYPE_ID_CLASS'], $data['IBLOCK_TYPE_ID']);
        if (!$typeID) return null;

        $unit = new static($data['NAME'], $data['CODE'] ?: '', $typeID);
        return $unit->setParams($data);
    }

    public static function get(array $filter): ?IBase
    {
        $ID = intval($filter['ID']);
        if ($ID < 1) return null;
        
        Loader::includeModule('iblock');

        $data = \CIBlock::GetByID($ID)->Fetch();
        if (!$data) return null;

        if (isset($filter['TYPE_ID_CLASS']))
            $data['TYPE_ID_CLASS'] = $filter['TYPE_ID_CLASS'];

        $unit = static::init($data);
        $unit->ID = $ID;
        return $unit;
    }

    public static function getDefaultParams(): array
    {
        return [
            'DETAIL_PAGE_URL' => '',
            'LIST_PAGE_URL' => '',
            'SECTION_PAGE_URL' => '',
            'CANONICAL_PAGE_URL' => '',
            'WORKFLOW' => 'N',
            'BIZPROC' => 'N',
            'SITE_ID' => \CSite::GetDefSite(),
            // 'DESCRIPTION' => '',
            'DESCRIPTION_TYPE' => 'text',
            'RSS_ACTIVE' => 'Y',
            'RSS_FILE_ACTIVE' => 'N',
            'RSS_FILE_LIMIT' => '',
            'RSS_FILE_DAYS' => '',
            'RSS_YANDEX_ACTIVE' => 'N',
            'XML_ID' => '',
            'INDEX_ELEMENT' => 'Y',
            'INDEX_SECTION' => 'N',
            'SORT' => '100',
        ];
    }

    public function getMainParams(): array
    {
        return [
            'ACTIVE' => 'Y',
            'NAME' => $this->name,
            'CODE' => $this->code,
            'IBLOCK_TYPE_ID' => $this->typeID->getCode(),
            /**
             * VERSION определяет способ хранения значений свойств элементов инфоблока
             *     1 - в общей таблице
             *     2 - в отдельной
             * Но выбрано строго 2, так ка при работе с множественными значениями свойств
             * инфоблока могут быть проблемы из-за того, что при запросе элементов через
             * GetList на каждое значение свойства будет дан столько же раз тот же элемент
             */
            'VERSION' => 2
        ];
    }

    public function Properties(string $propertyClassName = Property::class)
    {
        if (!$this->ID) return;

        Loader::includeModule('iblock');

        $propertyClassName = ClassName::getLastRelative(Property::class, $propertyClassName);
        $properties = \CIBlockProperty::GetLIst(['ID' => 'ASC'], ['IBLOCK_ID' => $this->ID]);
        while ($property = $properties->Fetch()) {
            $result = $propertyClassName::init($property + ['IBLOCK_CLASS' => $this]);
            $special_result = (new \ReflectionProperty($result, 'ID'));
            $special_result->setAccessible(true);
            $special_result->setValue($result, $property['ID']);
            yield $result;
        }
    }

    public function getProperties(string $propertyClassName = Property::class)
    {
        return iterator_to_array($this->Properties($propertyClassName));
    }

    public function getNewProperty(string $name, string $code, string $propertyClassName = Property::class): ?Property
    {
        if (!$this->ID) return null;

        return ClassName::getInstance(Property::class, $propertyClassName, $name, $code, $this);
    }

    public function setGroupPermissions(array $permissions = [])
    {
        if (!$this->ID) return $this;

        Loader::includeModule('iblock');

        \CIBlock::SetPermission($this->ID, $permissions);
        return $this;
    }

    public function save()
    {
        if (!$this->typeID->isExists()) {
            $this->errorText = Loc::getMessage(
                                    'ERROR_BAD_TYPE_ID_FOR_CREATING',
                                    ['#CLASSNAME#' => get_class($this->typeID)]
                                );
            return 0;
        }

        if ($this->ID) return $this->ID;

        Loader::includeModule('iblock');

        $iblock = new \CIBlock;
        $this->ID = $iblock->Add($this->getNormalizedParams());
        if (!$this->ID) $this->errorText = $iblock->LAST_ERROR;

        return $this->ID;
    }

    public static function remove(array $filter): bool
    {
        $iblock = static::get($filter);
        if (!$iblock) return false;

        \CIBlock::Delete($iblock->ID);
        return true;
    }
}