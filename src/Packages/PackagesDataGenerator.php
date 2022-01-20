<?php declare(strict_types = 1);

namespace Orisai\Installer\Packages;

use Composer\Composer;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use function array_map;
use function dirname;
use function ltrim;
use function strlen;
use function substr;

/**
 * @internal
 */
final class PackagesDataGenerator
{

	private Composer $composer;

	private string $rootDir;

	public function __construct(Composer $composer)
	{
		$this->composer = $composer;
		$this->rootDir = dirname($composer->getConfig()->get('vendor-dir'));
	}

	public function generate(): PackagesData
	{
		$rootPackageData = $this->packageToData(
			$this->composer->getPackage(),
		);

		$data = new PackagesData($this->rootDir, $rootPackageData);

		$repository = $this->composer->getRepositoryManager()->getLocalRepository();
		foreach ($repository->getCanonicalPackages() as $package) {
			$data->addPackage($this->packageToData($package));
		}

		PackagesDataStorage::save($data);

		return $data;
	}

	private function packageToData(PackageInterface $package): PackageData
	{
		return new PackageData(
			$package->getName(),
			$this->convertLinks($package->getRequires()),
			$this->convertLinks($package->getDevRequires()),
			$this->convertLinks($package->getReplaces()),
			$this->getRelativePath($package),
			$this->getAbsolutePath($package),
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
			return $this->rootDir;
		}

		return $this->composer->getInstallationManager()->getInstallPath($package);
	}

	private function getRelativePath(PackageInterface $package): string
	{
		return ltrim(substr($this->getAbsolutePath($package), strlen($this->rootDir)), '/');
	}

}
