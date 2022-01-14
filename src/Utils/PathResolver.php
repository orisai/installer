<?php declare(strict_types = 1);

namespace Orisai\Installer\Utils;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Orisai\Installer\Monorepo\MonorepoSubpackage;
use function dirname;
use function file_exists;
use function implode;
use function ltrim;
use function mb_strlen;
use function mb_substr;
use function realpath;
use function strlen;
use function substr;

/**
 * @internal
 */
final class PathResolver
{

	private Composer $composer;

	public function __construct(Composer $composer)
	{
		$this->composer = $composer;
	}

	public function getAbsolutePath(PackageInterface $package): string
	{
		if ($package === $this->composer->getPackage()) {
			return $this->getRootDir();
		}

		if ($package instanceof MonorepoSubpackage) {
			return $package->getPackageDirectory();
		}

		return $this->composer->getInstallationManager()->getInstallPath($package);
	}

	public function getRelativePath(PackageInterface $package): string
	{
		return ltrim(substr($this->getAbsolutePath($package), strlen($this->getRootDir())), '/');
	}

	public function getSchemaFileFullName(PackageInterface $package, string $unresolvedFileName): string
	{
		// File name is absolute, use it
		if (realpath($unresolvedFileName) === $unresolvedFileName && file_exists($unresolvedFileName)) {
			return $unresolvedFileName;
		}

		return $this->getAbsolutePath($package) . '/' . $unresolvedFileName;
	}

	/**
	 * Relative path to schema file resolved from schema file fqn
	 */
	public function getSchemaFileRelativeName(PackageInterface $package, string $schemaFileFqn): string
	{
		return mb_substr($schemaFileFqn, mb_strlen($this->getAbsolutePath($package)) + 1);
	}

	public function getRootDir(): string
	{
		// Composer supports ProjectInstaller only during create-project command so let's hope no-one change vendor-dir
		return dirname($this->composer->getConfig()->get('vendor-dir'));
	}

	/**
	 * @param array<mixed> $parts
	 */
	public function buildPathFromParts(array $parts): string
	{
		$paths = [];

		foreach ($parts as $part) {
			if ($part !== '/' && $part !== '') {
				$paths[] = $part;
			}
		}

		return implode('/', $paths);
	}

}
