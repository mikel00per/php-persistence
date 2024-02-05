<?php

namespace Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use JsonException;
use Shared\Infrastructure\Doctrine\Dbal\DoctrineCustomType;

use function Lambdish\Phunctional\last;

abstract class ArrayJson extends JsonType implements DoctrineCustomType
{
    abstract protected function typeClassName(): string;

    public static function customTypeName(): string
    {
        $text =  str_replace('Type', '', (string) last(explode('\\', static::class)));

        return ctype_lower($text) ?
            $text :
            strtolower((string) preg_replace('/([^A-Z\s])([A-Z])/', '$1_$2', $text))
        ;
    }

    final public function getName(): string
    {
        return self::customTypeName();
    }

    final public function convertToPHPValue($value, AbstractPlatform $platform): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ConversionException::conversionFailed($value, $this->getName(), $e);
        }
    }

    final public function convertToDatabaseValue($value, AbstractPlatform $platform): false|string
    {
        if ($value === null) {
            return '[]';
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        } catch (JsonException $e) {
            throw ConversionException::conversionFailedSerialization($value, 'json', $e->getMessage(), $e);
        }
    }
}