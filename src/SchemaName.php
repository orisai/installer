<?php declare(strict_types = 1);

namespace Orisai\Installer;

/**
 * @internal
 */
final class SchemaName
{

	public const DefaultName = 'Orisai.php';

	public const FileLocations = [
		self::DefaultName,
		'src/' . self::DefaultName,
		'app/' . self::DefaultName,
	];

}
