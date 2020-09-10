<?php declare(strict_types = 1);

namespace Orisai\Installer\Utils;

use Composer\Package\PackageInterface;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Config\PackageConfig;
use function file_exists;
use function sprintf;

final class PluginActivator
{

	private PackageInterface $rootPackage;
	private ConfigValidator $validator;
	private PathResolver $pathResolver;
	private string $unresolvedFileName;

	private ?PackageConfig $config = null;
	private ?string $schemaFileFullName = null;

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
		if (!file_exists($this->getSchemaFileFullName())) {
			return false;
		}

		return $this->getRootPackageConfiguration()->getLoader() !== null;
	}

	public function getRootPackageConfiguration(): PackageConfig
	{
		if ($this->config !== null) {
			return $this->config;
		}

		if (!file_exists($this->getSchemaFileFullName())) {
			throw InvalidState::create()
				->withMessage(sprintf(
					'Plugin is not activated, check with \'%s()\' before calling \'%s\'',
					self::class . '::isEnabled()',
					__METHOD__ . '()',
				));
		}

		return $this->config = $this->validator->validateConfiguration($this->rootPackage, $this->unresolvedFileName);
	}

	private function getSchemaFileFullName(): string
	{
		if ($this->schemaFileFullName !== null) {
			return $this->schemaFileFullName;
		}

		return $this->schemaFileFullName = $this->pathResolver->getSchemaFileFullName(
			$this->rootPackage,
			$this->unresolvedFileName,
		);
	}

}
