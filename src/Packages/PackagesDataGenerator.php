<?php declare(strict_types = 1);

namespace Orisai\Installer\Packages;

use Composer\Composer;
use Composer\Installer\InstallationManager;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use function array_map;
use function assert;
use function getcwd;
use function is_string;
use function ltrim;
use function strlen;
use function substr;

/**
 * @internal
 */
final class PackagesDataGenerator
{

	private Composer $composer;

	private InstallationManager $installationManager;

	public function __construct(Composer $composer)
	{
		$this->composer = $composer;
		$this->installationManager = $composer->getInstallationManager();
	}

	public function generate(): PackagesData
	{
		$rootPackage = $this->composer->getPackage();
		$rootDir = $this->getAbsolutePath($rootPackage);
		$rootPackageData = $this->packageToData($rootPackage, $rootDir);

		$data = new PackagesData($rootPackageData);

		$repository = $this->composer->getRepositoryManager()->getLocalRepository();
		foreach ($repository->getCanonicalPackages() as $package) {
			$data->addPackage($this->packageToData($package, $rootDir));
		}

		PackagesDataStorage::save($data);

		return $data;
	}

	private function packageToData(PackageInterface $package, string $rootDir): PackageData
	{
		return new PackageData(
			$package->getName(),
			$this->convertLinks($package->getRequires()),
			$this->convertLinks($package->getDevRequires()),
			$this->convertLinks($package->getReplaces()),
			$absolutePath = $this->getAbsolutePath($package),
			$this->getRelativePath($absolutePath, $rootDir),
		);
	}

	/**
	 * @param array<Link> $links
	 * @return array<PackageLink>
	 */
	private function convertLinks(array $links): array
	{
		return array_map(
			static fn (Link $link) => new PackageLink($link->getSource(), $link->getTarget()),
			$links,
		);
	}

	private function getAbsolutePath(PackageInterface $package): string
	{
		if ($package === $this->composer->getPackage()) {
			$cwd = getcwd();
			assert(is_string($cwd));

			return $cwd;
		}

		return $this->installationManager->getInstallPath($package);
	}

	private function getRelativePath(string $absolutePath, string $rootDir): string
	{
		return ltrim(substr($absolutePath, strlen($rootDir)), '/');
	}

}
