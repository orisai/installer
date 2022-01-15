<?php declare(strict_types = 1);

namespace Orisai\Installer\Utils;

use Composer\Package\PackageInterface;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Config\PackageConfig;
use function file_exists;

/**
 * @internal
 */
final class PluginActivator
{

	private PackageInterface $rootPackage;

	private ConfigValidator $validator;

	private PathResolver $pathResolver;

	private string $unresolvedFileName;

	public function __construct(
		PackageInterface $rootPackage,
		ConfigValidator $validator,
		PathResolver $pathResolver,
		string $unresolvedFileName
	)
	{
		$this->rootPackage = $rootPackage;
		$this->validator = $validator;
		$this->pathResolver = $pathResolver;
		$this->unresolvedFileName = $unresolvedFileName;
	}

	public function isEnabled(): bool
	{
		return file_exists($this->getSchemaFileFullName());
	}

	public function getRootPackageConfiguration(): PackageConfig
	{
		return $this->validator->validateConfiguration($this->rootPackage, $this->unresolvedFileName);
	}

	private function getSchemaFileFullName(): string
	{
		return $this->pathResolver->getSchemaFileFullName(
			$this->rootPackage,
			$this->unresolvedFileName,
		);
	}

}
