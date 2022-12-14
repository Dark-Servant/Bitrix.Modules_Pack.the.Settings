<?
namespace PackTheSettings\Settings;

use \PackTheSettings\Code\Comment;
use \PackTheSettings\Settings\Data\IBase;

class Printer
{
    const COMMENT_CONST_NAME = 0;
    const COMMENT_LANG_NAME = 1;
    const COMMENT_AT_CONSTANTS = 2;
    const COMMENT_AT_LANGS = 3;
    const COMMENT_AT_SETTINGS = 4;

    protected $data = [];
    protected $excludedData = [];

    protected $replacingID = [];
    protected $langIDsReplacing = [];

    protected static function getCategory(IBase $unit)
    {
        return constant(get_class($unit) . '::SETTINGS_CODE');
    }

    public function excludeData(IBase $unit): Printer
    {
        $category = static::getCategory($unit);
        if (empty($category)) return $this;

        $this->excludedData[$category][] = $unit->getID();
        return $this;
    }

    public function isExcluded(IBase $unit): bool
    {
        $category = static::getCategory($unit);

        return empty($category)
               || (
                    is_array($this->excludedData[$category])
                    && in_array($unit->getID(), $this->excludedData[$category])
               );
    }
    
    public function addData(IBase $unit): Printer
    {
        $category = static::getCategory($unit);
        $unitID = $unit->getID();

        if (
            empty($category) || empty($unitID)
            || isset($this->data[$category][$unitID])
            || ($unit->isPreparedForPrinter($this) !== true)
        ) return $this;

        $this->data[$category][$unitID] = $unit;
        $unit->finishPrinting($this);
        return $this;
    }

    public function isAdded(IBase $unit): bool
    {
        $category = static::getCategory($unit);
        $unitID = $unit->getID();

        return !empty($category) && !empty($unitID) && isset($this->data[$category][$unitID]);
    }

    protected function Data()
    {
        foreach ($this->data as $code => $units) {
            foreach ($units as $unit) {
                yield $code => $unit;
            }
        }
    }

    public function SpecialIDs()
    {
        foreach ($this->Data() as $unit) {
            if (!preg_match('/\{([^\{\}]*)\}[^\{\}]*$/', $unit->getID(), $IDParts))
                continue;

            yield $IDParts[1] => $unit->getComment(self::COMMENT_CONST_NAME, $this);
        }
    }

    public function getSpecialIDs()
    {
        return iterator_to_array($this->SpecialIDs());
    }

    public function replacingSpecialIDs(string $value)
    {
        return strtr($value, $this->replacingID);
    }

    public function SpecialLangIDs()
    {
        foreach ($this->Data() as $unit) {
            foreach ($unit->getLangValues($this) as $name => $value) {
                if (!preg_match('/\{([^\{\}]*)\}[^\{\}]*$/', $name, $nameParts))
                    continue;

                yield $nameParts[1] => [
                    'TITLE' => strtr($unit->getComment(self::COMMENT_LANG_NAME, $this), ['#VALUE#' => $value]),
                    'VALUE' => $value
                ];
            }
        }
    }

    public function getSpecialLangIDs()
    {
        return iterator_to_array($this->SpecialLangIDs());
    }

    public function replacingSpecialLangIDs(string $value)
    {
        return strtr($value, $this->langIDsReplacing);
    }

    protected static function setReplacingCodes(array&$result, array $data)
    {
        $result = [];
        foreach ($data as $code => $value) {
            if (
                !is_string($code)
                || empty($code = trim(preg_replace('/[\{\}]/', '', $code)))
                || !is_string($value)
            ) continue;
            
            $result['{' . $code . '}'] = $value;
        }
    }

    public function setReplacingSpecialIDs(array $data)
    {
        static::setReplacingCodes($this->replacingID, $data);
    }

    public function setReplacingSpecialLangIDs(array $data)
    {
        static::setReplacingCodes($this->langIDsReplacing, $data);
    }

    public function getConstantCode(): string
    {
        $result = '';
        foreach ($this->Data() as $unit) {
            $areaValue = '';
            foreach ($unit->getConstantValues($this) as $name => $value) {
                $resultName = $this->replacingSpecialIDs($name);
                if (is_string($value)) {
                    $areaValue .= sprintf('define(\'%s\', \'%s\');', $resultName, addslashes($value)) . PHP_EOL;

                } elseif (is_numeric($value)) {
                    $areaValue .= sprintf('define(\'%s\', %s);', $resultName, strval($value)) . PHP_EOL;

                } else {
                    continue;
                }
            }
            $result = ltrim($result . PHP_EOL)
                    . ltrim((new Comment($unit->getComment(self::COMMENT_AT_CONSTANTS, $this)))->getMultiLineStyle() . PHP_EOL)
                    . $areaValue;
        }
        return trim($result);
    }

    public function getLangCode(): string
    {
        $result = '';
        foreach ($this->Data() as $unit) {
            $result .= ltrim((new Comment($unit->getComment(self::COMMENT_AT_LANGS, $this)))->getMultiLineStyle() . PHP_EOL);
            foreach ($unit->getLangValues($this) as $name => $value) {
                $result .= sprintf('$MESS[\'%s\'] = \'%s\';', $this->replacingSpecialLangIDs($name), $value) . PHP_EOL;
            }
            $result .= PHP_EOL;
        }
        return trim($result);
    }

    public function getSettingsCode(): string
    {
        $result = '';
        $prevCode = false;
        foreach ($this->Data() as $code => $unit) {
            $settingValue = $unit->getShortSettings($this);
            if (empty($settingValue)) continue;

            if ($prevCode !== $code) {
                $prevCode = $code;

                $result .= (empty($result) ? '' : '],' . PHP_EOL . PHP_EOL)
                         . sprintf('\'%s\' => [', $code) . PHP_EOL;
            }
            $unitSettingValue = PHP_EOL . ltrim((new Comment($unit->getComment(self::COMMENT_AT_SETTINGS, $this)))->getMultiLineStyle() . PHP_EOL)
                              . '\'' . $this->replacingSpecialIDs($unit->getID()) . '\' => ' . Comment::getArrayAsStr($settingValue) . ',';
            $result .= (new Comment($unitSettingValue))->getWithSpaceValue(1);
        }
        return ltrim(rtrim($result) . PHP_EOL . ']', PHP_EOL . ']');
    }
}