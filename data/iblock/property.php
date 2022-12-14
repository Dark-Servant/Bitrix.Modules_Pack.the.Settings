<?
namespace PackTheSettings\Data\IBlock;

use \PackTheSettings\Arguments\ClassName;
use \PackTheSettings\Data\Base;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;

/**
 * TODO:
 *      1. IBlock "LIST" fields
 */
class Property extends Base
{
    protected $ID = false;
    protected $name;
    protected $code;
    protected $IBlock;
    protected $IBlockClassName;
    
    public function __construct(string $name, string $code, IBlock $IBlock)
    {
        $this->name = $name;
        $this->code = $code ?: md5($name);
        $this->IBlock = $IBlock;
        $this->IBlockClassName = get_class($IBlock);
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

    public function getIBlock(): IBlock
    {
        return $this->IBlock;
    }

    public function getLinkedIBlock()
    {
        return empty($this->params['LINK_IBLOCK_ID']) ? false : $this->params['LINK_IBLOCK_ID'];
    }
    
    public static function init(array $data)
    {
        if (!is_string($data['NAME'])) return;

        $iblock = ClassName::getInstanceViaID(IBlock::class, $data['IBLOCK_CLASS'], $data['IBLOCK_ID']);
        if (!$iblock) return;

        $unit = new static($data['NAME'], $data['CODE'] ?: '', $iblock);
        return $unit->setParams($data);
    }

    public static function get(array $filter)
    {
        $ID = intval($filter['ID']);
        if ($ID < 1) return;

        Loader::includeModule('iblock');

        $data = \CIBlockProperty::GetByID($ID)->Fetch();
        if (!$data) return;

        if (isset($filter['IBLOCK_CLASS']))
            $data['IBLOCK_CLASS'] = $filter['IBLOCK_CLASS'];
            
        $unit = static::init($data);
        $unit->ID = $ID;
        return $unit;
    }

    public static function getDefaultValues(): array
    {
        return [
            'SORT' => 100,
            'DEFAULT_VALUE' => '',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 30,
            'MULTIPLE' => 'N',
            'XML_ID' => '',
            'TMP_ID' => '',
            'LIST_TYPE' => 'L',
            'MULTIPLE_CNT' => 1,
            'IS_REQUIRED' => 'N',
            'VERSION' => 1,
            'USER_TYPE' => '',
            'HINT' => ''
        ];
    }

    public function getMainParams(): array
    {
        return [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => $this->IBlock->getID(),
            'NAME' => $this->name,
            'CODE' => $this->code,
        ];
    }

    protected static function prepareLinkedIBlock(array&$params, string $IBlockClassName)
    {
        if ($params['LINK_IBLOCK_ID'] instanceof IBlock)
            return;

        $params['LINK_IBLOCK_ID'] = $IBlockClassName::get(['ID' => $params['LINK_IBLOCK_ID']]);
        if (!isset($params['LINK_IBLOCK_ID']))
            throw new \Exception(Loc::getMessage('ERROR_BAD_LINK_IBLOCK_ID'));
    }

    protected static function prepareStringType(array&$params)
    {
        if (!array_key_exists('USER_TYPE_SETTINGS', $params))
            return;

        $params['USER_TYPE_SETTINGS'] = is_array($params['USER_TYPE_SETTINGS'])
                                      ? array_filter(
                                            $params['USER_TYPE_SETTINGS'],
                                            function($value) { return $value != 'N'; }
                                        )
                                      : [];
    }

    public function setParams(array $params)
    {
        $this->params = $params;

        if (!empty($this->params['LINK_IBLOCK_ID'])) {
            static::prepareLinkedIBlock($this->params, $this->IBlockClassName);

        } elseif ($this->params['PROPERTY_TYPE'] == 'S') {
            static::prepareStringType($this->params);
        }

        return $this;
    }

    public function getChangedValues(): array
    {
        $result = array_merge(
                        parent::getChangedValues(),
                        array_filter(
                            $this->params,
                            function($value, $key) {
                                return in_array($key, ['PROPERTY_TYPE', 'USER_TYPE', 'LINK_IBLOCK_ID',
                                                       'USER_TYPE_SETTINGS', 'FILE_TYPE'])
                                       && !empty($value);
                            },
                            ARRAY_FILTER_USE_BOTH
                        )
                    );
        if (isset($result['LINK_IBLOCK_ID']))
            $result['LINK_IBLOCK_ID'] = $result['LINK_IBLOCK_ID']->getID();

        return $result;
    }

    public function save()
    {
        if (!$this->IBlock->getID())
            throw new \Exception(
                        Loc::getMessage(
                            'ERROR_BAD_IBLOCK_ID_FOR_CREATING',
                            ['#CLASSNAME#' => $this->IBlockClassName]
                        )
                    );

        if ($this->ID) return $this->ID;

        Loader::includeModule('iblock');

        $property = new \CIBlockProperty;
        $this->ID = $property->Add($this->getNormalizedValues());
        if (!$this->ID) $this->errorText = $property->LAST_ERROR;

        return $this->ID;
    }
}