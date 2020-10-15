<?php declare(strict_types = 1);

namespace Orisai\Installer\Resolving;

use Composer\Json\JsonFile;
use Composer\Package\Link;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\ValidatingArrayLoader;
use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Semver\Constraint\EmptyConstraint;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Message;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Config\PackageConfig;
use Orisai\Installer\Monorepo\SimulatedPackage;
use Orisai\Installer\Plugin;
use Orisai\Installer\Utils\PathResolver;
use function array_merge;
use function assert;
use function file_exists;
use function file_get_contents;
use function in_array;
use function is_array;
use function ksort;
use function sprintf;
use function strtolower;
use function uasort;

final class ModuleResolver
{

	private WritableRepositoryInterface $repository;
	private PathResolver $pathResolver;
	private ConfigValidator $validator;
	private PackageConfig $rootPackageConfiguration;

	public function __construct(
		WritableRepositoryInterface $repository,
		PathResolver $pathResolver,
		ConfigValidator $validator,
		PackageConfig $rootPackageConfiguration
	)
	{
		$this->repository = $repository;
		$this->pathResolver = $pathResolver;
		$this->validator = $validator;
		$this->rootPackageConfiguration = $rootPackageConfiguration;
	}

	/**
	 * @return array<PackageConfig>
	 */
	public function getResolvedConfigurations(): array
	{
		/** @var array<PackageInterface> $packages */
		$packages = $this->repository->getCanonicalPackages();
		$packages = array_merge($packages, $this->getSimulatedPackages());

		/** @var array<Module> $modules */
		$modules = [];

		/** @var array<string> $ignored */
		$ignored = [];

		foreach ($packages as $package) {
			if (!$this->isApplicable($package)) {
				if ($package instanceof SimulatedPackage) {
					$message = Message::create()
						->withContext(sprintf(
							'Trying to set package `%s` as a simulated module of `%s`.',
							$package->getName(),
							$package->getParentName(),
						))
						->withProblem(sprintf('Package does not have `%s` file.', Plugin::DEFAULT_FILE_NAME))
						->withSolution(
							'Choose an package which does have required file or create the file or remove simulated module from configuration.',
						);

					throw InvalidArgument::create()
						->withMessage($message);
				}

				continue;
			}

			$modules[$package->getName()] = $module = new Module(
				$this->validator->validateConfiguration($package, Plugin::DEFAULT_FILE_NAME),
			);
			$ignored = array_merge($ignored, $module->getConfiguration()->getIgnoredPackages());
		}

		$ignored = array_merge($ignored, $this->rootPackageConfiguration->getIgnoredPackages());

		foreach ($modules as $module) {
			$module->setDependents(
				$this->packagesToModules(
					$this->flatten(
						$this->getDependents($module->getConfiguration()->getPackage()->getName(), $packages),
					),
					$modules,
				),
			);
		}

		uasort($modules, static function (Module $m1, Module $m2) {
			$d1 = $m1->getDependents();
			$n1 = $m1->getConfiguration()->getPackage()->getName();

			$d2 = $m2->getDependents();
			$n2 = $m2->getConfiguration()->getPackage()->getName();

			// Cyclical dependency, ignore
			if (isset($d1[$n2], $d2[$n1])) {
				return 0;
			}

			if (isset($d1[$n2])) {
				return -1;
			}

			return 1;
		});

		$packageConfigurations = [];

		foreach ($modules as $module) {
			// Skip package configuration if listed in ignored
			if (in_array($module->getConfiguration()->getPackage()->getName(), $ignored, true)) {
				continue;
			}

			$packageConfigurations[] = $module->getConfiguration();
		}

		$packageConfigurations[] = $this->rootPackageConfiguration;

		return $packageConfigurations;
	}

	/**
	 * Returns list of explicitly allowed packages, which are part of monorepo like they were really installed
	 *
	 * @return array<SimulatedPackage>
	 */
	public function getSimulatedPackages(): array
	{
		$loader = new ValidatingArrayLoader(new ArrayLoader());

		$parentPackage = $this->rootPackageConfiguration->getPackage();
		$parentPackageName = $parentPackage->getName();
		$parentFullPath = $this->pathResolver->getAbsolutePath($parentPackage);

		$packages = [];

		foreach ($this->rootPackageConfiguration->getSimulatedModules() as $module) {
			$expectedName = $module->getName();

			// Package exists, simulation not needed
			if ($this->repository->findPackage($expectedName, new EmptyConstraint()) !== null) {
				continue;
			}

			$path = $module->getPath();

			$directoryPath = $this->pathResolver->buildPathFromParts([
				$parentFullPath,
				$this->rootPackageConfiguration->getSchemaPath(),
				$path,
			]);
			$composerFilePath = $this->pathResolver->buildPathFromParts([
				$directoryPath,
				'composer.json',
			]);

			if (!file_exists($composerFilePath)) {
				if ($module->isOptional()) {
					continue;
				}

				$message = Message::create()
					->withContext(sprintf('Trying to setup simulated module `%s`.', $expectedName))
					->withProblem(sprintf('Package is not installed and file `%s` was not found.', $composerFilePath))
					->withSolution(
						sprintf(
							'Set correct relative path instead of `%s` to simulated module or mark it as optional.',
							$path,
						),
					);

				throw InvalidArgument::create()
					->withMessage($message);
			}

			$config = JsonFile::parseJson(
				file_get_contents($composerFilePath),
				$composerFilePath,
			) + ['version' => '999.999.999'];

			$package = $loader->load($config, SimulatedPackage::class);
			assert($package instanceof SimulatedPackage);
			$packageName = $package->getName();

			if ($expectedName !== $packageName) {
				$message = Message::create()
					->withContext('Trying to configure simulated package.')
					->withProblem(sprintf(
						'Path `%s` contains package `%s` while package `%s` was expected by configuration.',
						$path,
						$packageName,
						$expectedName,
					))
					->withSolution(sprintf(
						'Set correct path to `%s` or change expected package name to `%s`.',
						$expectedName,
						$packageName,
					));

				throw InvalidArgument::create()
					->withMessage($message);
			}

			$package->setParentName($parentPackageName);
			$package->setPackageDirectory($directoryPath);
			$packages[] = $package;
		}

		return $packages;
	}

	/**
	 * Filter out packages with no orisai.neon and root package (which is handled separately)
	 */
	private function isApplicable(PackageInterface $package): bool
	{
		static $cache = [];
		$name = $package->getName();

		return $cache[$name]
			?? $cache[$name] = (file_exists(
				$this->pathResolver->getSchemaFileFullName($package, Plugin::DEFAULT_FILE_NAME),
			)
				&& $package !== $this->rootPackageConfiguration->getPackage());
	}

	private function getPackageFromLink(Link $link): ?PackageInterface
	{
		static $cache = [];
		$name = $link->getTarget();

		return $cache[$name] ?? $cache[$name] = $this->repository->findPackage($name, new EmptyConstraint());
	}

	/**
	 * @param array<PackageInterface> $packages
	 * @param array<Module> $modules
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
	 * @param array<mixed> $dependents
	 * @return array<PackageInterface>
	 */
	private function flatten(array $dependents): array
	{
		$deps = [];

		foreach ($dependents as $dependent) {
			[$package, $children] = $dependent;
			assert($package instanceof PackageInterface);
			assert(is_array($children) || $children === null);

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
	 * @param string             $needle The package name to inspect.
	 * @param array<PackageInterface> $packages
	 * @param array<string>|null $packagesFound Used internally when recurring
	 * @return array<array<mixed>> ['packageName' => [$package, $dependents|null]]
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
			if (!$this->isApplicable($package)) {
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

				if ($resolvedDevPackage === null || !$this->isApplicable($resolvedDevPackage)) {
					unset($devLinks[$key]);
				}
			}

			$links += $devLinks;

			// Cross-reference all discovered links to the needles
			foreach ($links as $link) {
				if ($link->getTarget() === $needle) {
					// already resolved this node's dependencies
					if (in_array($link->getSource(), $packagesInTree, true)) {
						$results[$link->getSource()] = [$package, null];

						continue;
					}

					$packagesInTree[] = $link->getSource();
					$dependents = $this->getDependents($link->getSource(), $packages, $packagesInTree);
					$results[$link->getSource()] = [$package, $dependents];
				}
			}
		}

		ksort($results);

		return $results;
	}

}
