<?
namespace PackTheSettings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\Base;
use \Bitrix\Main\Loader;

class IBlock extends Base
{
    protected $ID = 0;
    protected $name;
    protected $code;
    protected $typeID;

    public function __construct(string $name, string $code, Type $typeID)
    {
        $this->name = $name;
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

    public static function init(array $data)
    {
        if (!is_string($data['NAME'])) return;

        $typeID = ClassName::getInstanceViaID(Type::class, $data['TYPE_ID_CLASS'], $data['IBLOCK_TYPE_ID']);
        if (!$typeID) return;

        $unit = new static($data['NAME'], $data['CODE'] ?: '', $typeID);
        return $unit->setParams($data);
    }

    public static function get(array $filter)
    {
        $ID = intval($filter['ID']);
        if ($ID < 1) return;
        
        Loader::includeModule('iblock');

        $data = \CIBlock::GetByID($ID)->Fetch();
        if (!$data) return;

        if (isset($filter['TYPE_ID_CLASS']))
            $data['TYPE_ID_CLASS'] = $filter['TYPE_ID_CLASS'];

        $unit = static::init($data);
        $unit->ID = $ID;
        return $unit;
    }

    public static function getDefaultValues(): array
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
            'SORT' => '500',
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

    public function setGroupPermissions(array $permissions = [])
    {
        if (!$this->ID) return $this;

        Loader::includeModule('iblock');

        \CIBlock::SetPermission($this->ID, $permissions);
        return $this;
    }

    public function save()
    {
        if (!$this->typeID->isExists())
            throw new \Exception(
                        Loc::getMessage(
                            'ERROR_BAD_TYPE_ID_FOR_CREATING',
                            ['#CLASSNAME#' => get_class($this->typeID)]
                        )
                    );

        if ($this->ID) return $this->ID;

        Loader::includeModule('iblock');

        $iblock = new \CIBlock;
        $this->ID = $iblock->Add($this->getNormalizedValues());
        if (!$this->ID) $this->errorText = $iblock->LAST_ERROR;

        return $this->ID;
    }
}