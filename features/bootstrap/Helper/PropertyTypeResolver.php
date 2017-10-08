<?php declare(strict_types=1);

namespace Tests\Helper;

class PropertyTypeResolver
{
    public static function formatToType(string $type, $value)
    {
        switch ($type) {
            case 'bigint':
            case 'integer':
            case 'smallint':
                return (int)round($value);
                break;
            case 'boolean':
                if (false !== in_array(strtolower($value), ['true', '1'])) {
                    return true;
                }

                return false;
                break;
            case 'datetime':
            case 'date':
            case 'time':
                try {
                    return new \DateTime($value);
                } catch (\Exception $e) {
                    throw new \Exception(sprintf('"%s" is not a supported date/time/datetime format.', $value));
                }
                break;
            case 'decimal':
            case 'float':
                return (float)$value;
                break;
            case 'simple_array':
                if (!is_array($value)) {
                    return [$value];
                }
                break;
            case 'json_array':
            case 'array':
                $data = json_decode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
                if (!is_array($value)) {
                    return [$value];
                }
                break;
            default:
                return (string)$value;
        }
    }
}
