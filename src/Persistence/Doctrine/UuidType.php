<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Persistence\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Shared\Domain\ValueObjects\Uuid;
use Shared\Infrastructure\Doctrine\Dbal\DoctrineCustomType;

use function Lambdish\Phunctional\last;

abstract class UuidType extends StringType implements DoctrineCustomType
{
	abstract protected function typeClassName(): string;

	final public static function customTypeName(): string
	{
		$text = str_replace('Type', '', (string) last(explode('\\', static::class)));

		return ctype_lower($text) ? $text : strtolower((string) preg_replace('/([^A-Z\s])([A-Z])/', '$1_$2', $text));
	}

	final public function getName(): string
	{
		return self::customTypeName();
	}

	final public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		$className = $this->typeClassName();

		return new $className($value);
	}

	final public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		/** @var Uuid $value */
		return $value->value();
	}
}
