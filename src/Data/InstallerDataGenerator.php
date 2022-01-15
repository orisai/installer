<?php declare(strict_types = 1);

namespace Orisai\Installer\Data;

use Composer\Json\JsonFile;
use Composer\Package\Link as ComposerLink;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\ValidatingArrayLoader;
use Composer\Package\PackageInterface;
use Composer\Repository\WritableRepositoryInterface;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Message;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Config\PackageConfig;
use Orisai\Installer\Plugin;
use Orisai\Installer\Resolving\Link;
use Orisai\Installer\Utils\PathResolver;
use function array_map;
use function file_exists;
use function file_get_contents;
use function sprintf;

/**
 * @internal
 */
final class InstallerDataGenerator
{

	private WritableRepositoryInterface $repository;

	private ConfigValidator $configValidator;

	private PathResolver $pathResolver;

	public function __construct(
		WritableRepositoryInterface $repository,
		ConfigValidator $configValidator,
		PathResolver $pathResolver
	)
	{
		$this->repository = $repository;
		$this->configValidator = $configValidator;
		$this->pathResolver = $pathResolver;
	}

	public function generate(PackageInterface $rootPackage, PackageConfig $rootConfig): InstallerData
	{
		$rootPackageData = new InstallablePackageData(
			$rootPackage->getName(),
			$this->convertLinks($rootPackage->getRequires()),
			$this->convertLinks($rootPackage->getDevRequires()),
			$this->convertLinks($rootPackage->getReplaces()),
			$rootConfig,
			$this->pathResolver->getRelativePath($rootPackage),
			$this->pathResolver->getAbsolutePath($rootPackage),
		);

		$data = new InstallerData($this->pathResolver->getRootDir(), $rootPackageData);

		foreach ($this->repository->getCanonicalPackages() as $package) {
			$packageData = !$this->isInstallable($package) ? new PackageData(
				$package->getName(),
				$this->convertLinks($package->getRequires()),
				$this->convertLinks($package->getDevRequires()),
				$this->convertLinks($package->getReplaces()),
			) : new InstallablePackageData(
				$package->getName(),
				$this->convertLinks($package->getRequires()),
				$this->convertLinks($package->getDevRequires()),
				$this->convertLinks($package->getReplaces()),
				$this->configValidator->validateConfiguration($package, Plugin::DEFAULT_FILE_NAME),
				$this->pathResolver->getRelativePath($package),
				$this->pathResolver->getAbsolutePath($package),
			);

			$data->addPackage($packageData);
		}

		// TODO - add monorepo packages for all repositories
		//		- skip existing (installed) packages
		//		- handle identical package in two monorepos?
		foreach ($this->getMonorepoPackages($data, $rootPackageData) as $packageData) {
			$data->addPackage($packageData);
		}

		return $data;
	}

	private function isInstallable(PackageInterface $package): bool
	{
		return file_exists(
			$this->pathResolver->getSchemaFileFullName($package, Plugin::DEFAULT_FILE_NAME),
		);
	}

	/**
	 * Returns list of explicitly allowed packages, which are part of monorepo like they were really installed
	 *
	 * @return array<MonorepoPackageData>
	 */
	private function getMonorepoPackages(InstallerData $data, InstallablePackageData $rootPackage): array
	{
		$loader = new ValidatingArrayLoader(new ArrayLoader());

		$parentFullPath = $rootPackage->getAbsolutePath();

		$packages = [];

		foreach ($rootPackage->getConfig()->getSchema()->getMonorepoPackages() as $module) {
			$expectedName = $module->getName();

			// Package exists, simulation not needed
			if ($data->getPackage($expectedName) !== null) {
				continue;
			}

			$path = $module->getPath();

			$directoryPath = PathResolver::buildPathFromParts([
				$parentFullPath,
				$rootPackage->getConfig()->getSchemaPath(),
				$path,
			]);
			$composerFilePath = PathResolver::buildPathFromParts([
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

			$package = $loader->load($config);
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

			$packages[] = new MonorepoPackageData(
				$package->getName(),
				$this->convertLinks($package->getRequires()),
				$this->convertLinks($package->getDevRequires()),
				$this->convertLinks($package->getReplaces()),
				$this->configValidator->validateConfiguration($package, Plugin::DEFAULT_FILE_NAME),
				$this->pathResolver->getRelativePath($package),
				$directoryPath,
				$rootPackage,
			);
		}

		return $packages;
	}

	/**
	 * @param array<ComposerLink> $links
	 * @return array<Link>
	 */
	private function convertLinks(array $links): array
	{
		return array_map(
			static fn (ComposerLink $link) => new Link($link->getSource(), $link->getTarget()),
			$links,
		);
	}

}
