<?
namespace PackTheSettings\Code;

use \Bitrix\Main\Localization\Loc;

class Comment
{
    protected $value;

    const SPACE_SYMBOL = ' ';
    const STEP_SIZE = 4;

    public static function getSpaceValue(int $step = 0, int $size = self::STEP_SIZE, string $symbol = self::SPACE_SYMBOL)
    {
        return implode('', array_fill(0, $step * $size, $symbol));
    }

    public static function getArrayAsStr(array $value, int $step = 0, int $size = self::STEP_SIZE, string $symbol = self::SPACE_SYMBOL)
    {
        $result = '';
		$spaceValue = static::getSpaceValue($step + 1, $size, $symbol);
        foreach ($value as $key => $val) {
			$result .= $spaceValue;
			if (is_string($key))
				$result .= sprintf('\'%s\' => ', addslashes($key));

            if (is_string($val) || is_null($val)) {
                $result .= sprintf('\'%s\'', addslashes($val));

            } elseif (is_numeric($val)) {
                $result .= strval($val);

            } elseif (is_array($val)) {
                $result .= ltrim(self::getArrayAsStr($val, $step + 1));

            } else {
                throw new \Exception(Loc::getMessage('ERROR_BAD_VALUE'));
            }
            $result .= ',' . PHP_EOL;
        }
        $spaceValue = static::getSpaceValue($step, $size, $symbol);
		return $spaceValue . '[' . PHP_EOL . rtrim($result) . PHP_EOL . $spaceValue . ']';
    }

    public function __construct($value)
    {
        if (is_string($value) || is_numeric($value)) {
            $this->value = strval($value);

        } elseif (is_array($value)) {
            $this->value = static::getArrayAsStr($value);

        } else {
            throw new \Exception(Loc::getMessage('ERROR_BAD_VALUE'));
        }
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getMultiLineStyle()
    {
        if (empty($this->value)) return $this->value;
        
        $result = '/**' . PHP_EOL;
        foreach (explode(PHP_EOL, $this->value) as $line) {
            $result .= ' * ' . $line . PHP_EOL;
        }
        return $result . ' */';
    }

    public function getWithSpaceValue(int $step = 0, int $size = self::STEP_SIZE, string $symbol = self::SPACE_SYMBOL)
    {
        $result = '';
        $spaceValue = static::getSpaceValue($step, $size, $symbol);
        foreach (explode(PHP_EOL, $this->value) as $line) {
            $result .= $spaceValue . $line . PHP_EOL;
        }
        return $result;
    }
}