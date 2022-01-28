<?php declare(strict_types = 1);

namespace Orisai\Installer;

/**
 * @internal
 */
final class SchemaName
{

	public const DEFAULT_NAME = 'Orisai.php';

	public const FILE_LOCATIONS = [
		self::DEFAULT_NAME,
		'src/' . self::DEFAULT_NAME,
		'app/' . self::DEFAULT_NAME,
	];

}
