<?php declare(strict_types = 1);

namespace Orisai\Installer\Resolving;

use Orisai\Installer\Data\InstallablePackageData;
use Orisai\Installer\Data\InstallerData;
use Orisai\Installer\Data\PackageData;
use function in_array;
use function ksort;
use function strtolower;
use function uasort;

/**
 * @internal
 */
final class ModuleResolver
{

	private InstallerData $data;

	public function __construct(InstallerData $data)
	{
		$this->data = $data;
	}

	/**
	 * @return array<InstallablePackageData>
	 */
	public function getResolvedConfigurations(): array
	{
		$modules = [];

		foreach ($this->data->getPackages() as $package) {
			if (!$package instanceof InstallablePackageData || $package === $this->data->getRootPackage()) {
				continue;
			}

			$modules[$package->getName()] = new Module($package);
		}

		foreach ($modules as $module) {
			$module->setDependents(
				$this->packagesToModules(
					$this->flatten(
						$this->getDependents($module->getPackage()->getName(), $this->data->getPackages()),
					),
					$modules,
				),
			);
		}

		uasort($modules, static function (Module $m1, Module $m2) {
			$d1 = $m1->getDependents();
			$n1 = $m1->getPackage()->getName();

			$d2 = $m2->getDependents();
			$n2 = $m2->getPackage()->getName();

			// Cyclical dependency, ignore
			if (isset($d1[$n2], $d2[$n1])) {
				return 0;
			}

			if (isset($d1[$n2])) {
				return -1;
			}

			return 1;
		});

		$packages = [];

		foreach ($modules as $module) {
			$packages[] = $module->getPackage();
		}

		$packages[] = $this->data->getRootPackage();

		return $packages;
	}

	private function getPackageFromLink(Link $link): ?PackageData
	{
		return $this->data->getPackage($link->getTarget());
	}

	/**
	 * @param array<PackageData> $packages
	 * @param array<Module>      $modules
	 * @return array<Module>
	 */
	private function packagesToModules(array $packages, array $modules): array
	{
		$result = [];

		foreach ($packages as $package) {
			$name = $package->getName();
			if (isset($modules[$name])) {
				$result[$name] = $modules[$name];
			}
		}

		return $result;
	}

	/**
	 * @param array<array{PackageData, array<mixed>|null}> $dependents
	 * @return array<PackageData>
	 */
	private function flatten(array $dependents): array
	{
		$deps = [];

		foreach ($dependents as $dependent) {
			/** @var array<array{PackageData, array<mixed>|null}>|null $children */
			[$package, $children] = $dependent;
			$name = $package->getName();

			if (!isset($deps[$name])) {
				$deps[$name] = $package;
			}

			if ($children !== null) {
				$deps += $this->flatten($children);
			}
		}

		return $deps;
	}

	/**
	 * Returns a list of packages causing the requested needle packages to be installed.
	 *
	 * @param string             $needle        The package name to inspect.
	 * @param array<PackageData> $packages
	 * @param array<string>|null $packagesFound Used internally when recurring
	 * @return array<string, array{PackageData, array<mixed>|null}>
	 */
	private function getDependents(string $needle, array $packages, ?array $packagesFound = null): array
	{
		$needle = strtolower($needle);
		$results = [];

		// Initialize the array with the needles before any recursion occurs
		if ($packagesFound === null) {
			$packagesFound = [$needle];
		}

		// Loop over all currently installed packages.
		foreach ($packages as $package) {
			// Skip non-module packages
			if (!$package instanceof InstallablePackageData || $package === $this->data->getRootPackage()) {
				continue;
			}

			$links = $package->getRequires();

			// Each loop needs its own "tree" as we want to show the complete dependent set of every needle
			// without warning all the time about finding circular deps
			$packagesInTree = $packagesFound;

			// Replaces are relevant for order
			$links += $package->getReplaces();

			// Only direct dev-requires are relevant and only if they represent modules
			$devLinks = $package->getDevRequires();

			foreach ($devLinks as $key => $link) {
				$resolvedDevPackage = $this->getPackageFromLink($link);

				if (
					!$resolvedDevPackage instanceof InstallablePackageData
					|| $package === $this->data->getRootPackage()
				) {
					unset($devLinks[$key]);
				}
			}

			$links += $devLinks;

			// Cross-reference all discovered links to the needles
			foreach ($links as $link) {
				if ($link->getTarget() === $needle) {
					// already resolved this node's dependencies
					$source = $link->getSource();
					if (in_array($source, $packagesInTree, true)) {
						$results[$source] = [$package, null];

						continue;
					}

					$packagesInTree[] = $source;
					$dependents = $this->getDependents($source, $packages, $packagesInTree);
					$results[$source] = [$package, $dependents];
				}
			}
		}

		ksort($results);

		return $results;
	}

}
