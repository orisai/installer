<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Packages\PackageLink;
use Orisai\Installer\Packages\PackagesData;
use function in_array;
use function ksort;
use function strtolower;
use function uasort;

/**
 * @internal
 */
final class ModuleSorter
{

	/**
	 * @param array<string, Module> $modules
	 * @return array<string, Module>
	 */
	public function getSortedModules(array $modules, PackagesData $data): array
	{
		foreach ($modules as $module) {
			$module->setDependents(
				$this->packagesToModules(
					$this->flatten(
						$this->getDependents($modules, $data, $module->getData()->getName(), $data->getPackages()),
					),
					$modules,
				),
			);
		}

		uasort($modules, static function (Module $m1, Module $m2) {
			$d1 = $m1->getDependents();
			$n1 = $m1->getData()->getName();

			$d2 = $m2->getDependents();
			$n2 = $m2->getData()->getName();

			// Cyclical dependency, ignore
			if (isset($d1[$n2], $d2[$n1])) {
				return 0;
			}

			if (isset($d1[$n2])) {
				return -1;
			}

			return 1;
		});

		return $modules;
	}

	private function getPackageFromLink(PackageLink $link, PackagesData $data): ?PackageData
	{
		return $data->getPackage($link->getTarget());
	}

	/**
	 * @param array<string, PackageData> $packages
	 * @param array<string, Module>      $modules
	 * @return array<string, Module>
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
	 * @return array<string, PackageData>
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
	 * @param array<string, Module> $modules
	 * @param string                $needle        The package name to inspect.
	 * @param array<PackageData>    $packages
	 * @param array<string>|null    $packagesFound Used internally when recurring
	 * @return array<string, array{PackageData, array<mixed>|null}>
	 */
	private function getDependents(
		array $modules,
		PackagesData $data,
		string $needle,
		array $packages,
		?array $packagesFound = null
	): array
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
			if (!isset($modules[$package->getName()])) {
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
				$resolvedDevPackage = $this->getPackageFromLink($link, $data);

				if ($resolvedDevPackage === null || !isset($modules[$resolvedDevPackage->getName()])) {
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
					$dependents = $this->getDependents($modules, $data, $source, $packages, $packagesInTree);
					$results[$source] = [$package, $dependents];
				}
			}
		}

		ksort($results);

		return $results;
	}

}
