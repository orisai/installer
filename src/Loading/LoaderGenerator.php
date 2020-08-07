<?php declare(strict_types = 1);

namespace Orisai\Installer\Loading;

use Composer\Repository\WritableRepositoryInterface;
use Composer\Semver\Constraint\EmptyConstraint;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Config\FileConfig;
use Orisai\Installer\Config\LoaderConfig;
use Orisai\Installer\Config\PackageConfig;
use Orisai\Installer\Files\Writer;
use Orisai\Installer\Resolving\ModuleResolver;
use Orisai\Installer\Utils\PathResolver;
use Orisai\Installer\Utils\PluginActivator;
use ReflectionClass;
use function array_merge;
use function class_exists;
use function is_subclass_of;
use function sprintf;
use function strrchr;
use function strrpos;
use function substr;

final class LoaderGenerator
{

	private const LOADER_PROPERTY_SCHEMA = 'schema';
	private const LOADER_PROPERTY_MODULES_META = 'modulesMeta';
	private const LOADER_PROPERTY_SWITCHES = 'switches';

	/** @var WritableRepositoryInterface */
	private $repository;

	/** @var Writer */
	private $writer;

	/** @var PathResolver */
	private $pathResolver;

	/** @var ConfigValidator */
	private $validator;

	/** @var PackageConfig */
	private $rootPackageConfiguration;

	public function __construct(
		WritableRepositoryInterface $repository,
		Writer $writer,
		PathResolver $pathResolver,
		ConfigValidator $validator,
		PackageConfig $rootPackageConfiguration
	)
	{
		$this->repository = $repository;
		$this->writer = $writer;
		$this->pathResolver = $pathResolver;
		$this->validator = $validator;
		$this->rootPackageConfiguration = $rootPackageConfiguration;
	}

	public function generateLoader(): void
	{
		$loaderConfiguration = $this->rootPackageConfiguration->getLoader();

		if ($loaderConfiguration === null) {
			throw new InvalidState(sprintf(
				'Loader should be always available by this moment. Entry point should check if plugin is activated with \'%s\'',
				PluginActivator::class
			));
		}

		$resolver = new ModuleResolver(
			$this->repository,
			$this->pathResolver,
			$this->validator,
			$this->rootPackageConfiguration
		);

		$this->generateClass($loaderConfiguration, $resolver->getResolvedConfigurations());
	}

	/**
	 * @param array<PackageConfig> $packageConfigurations
	 */
	private function generateClass(LoaderConfig $loaderConfiguration, array $packageConfigurations): void
	{
		$fqn = $loaderConfiguration->getClass();
		$lastSlashPosition = strrpos($fqn, '\\');

		if ($lastSlashPosition === false) {
			throw new InvalidArgument('Namespace of loader class must be specified.');
		}

		$itemsByPriority = [
			FileConfig::PRIORITY_VALUE_HIGH => [],
			FileConfig::PRIORITY_VALUE_NORMAL => [],
			FileConfig::PRIORITY_VALUE_LOW => [],
		];

		$modulesMeta = [];

		$switchesByPackage = [];

		foreach ($packageConfigurations as $packageConfiguration) {
			$switchesByPackage[] = $packageConfiguration->getSwitches();
		}

		$switches = array_merge(...$switchesByPackage);

		foreach ($packageConfigurations as $packageConfiguration) {
			$package = $packageConfiguration->getPackage();
			$packageName = $package->getName();
			$packageDirRelative = $this->pathResolver->getRelativePath($package);

			if ($packageName !== '__root__') {
				$modulesMeta[$package->getName()] = [
					BaseLoader::META_ITEM_DIR => $packageDirRelative,
				];
			}

			foreach ($packageConfiguration->getFiles() as $fileConfiguration) {
				// Skip configuration if required package is not installed
				foreach ($fileConfiguration->getRequiredPackages() as $requiredPackage) {
					if ($this->repository->findPackage($requiredPackage, new EmptyConstraint()) === null) {
						continue 2;
					}
				}

				$item = [
					BaseLoader::SCHEMA_ITEM_FILE => $this->pathResolver->buildPathFromParts([
						$packageDirRelative,
						$packageConfiguration->getSchemaPath(),
						$fileConfiguration->getFile(),
					]),
				];

				$itemSwitches = $fileConfiguration->getSwitches();

				foreach ($itemSwitches as $itemSwitchName => $itemSwitchValue) {
					if (!isset($switches[$itemSwitchName])) {
						throw new InvalidArgument(sprintf(
							'Configuration file switch \'%s\' is not defined in \'%s\'.',
							$itemSwitchName,
							PackageConfig::SWITCHES_OPTION
						));
					}
				}

				if ($itemSwitches !== []) {
					$item[BaseLoader::SCHEMA_ITEM_SWITCHES] = $itemSwitches;
				}

				$itemsByPriority[$fileConfiguration->getPriority()][] = $item;
			}
		}

		$schema = array_merge(
			$itemsByPriority[FileConfig::PRIORITY_VALUE_HIGH],
			$itemsByPriority[FileConfig::PRIORITY_VALUE_NORMAL],
			$itemsByPriority[FileConfig::PRIORITY_VALUE_LOW]
		);

		if (class_exists($fqn)) {
			if (!is_subclass_of($fqn, BaseLoader::class)) {
				throw new InvalidState(sprintf(
					'\'%s\' should be instance of \'%s\'',
					$fqn,
					BaseLoader::class
				));
			}

			$loaderReflection = new ReflectionClass($fqn);
			$loaderProperties = $loaderReflection->getDefaultProperties();

			if (
				$loaderProperties['schema'] === $schema
				&& $loaderProperties['modulesMeta'] === $modulesMeta
				&& $loaderProperties['switches'] === $switches
			) {
				return;
			}
		}

		$classString = substr($fqn, $lastSlashPosition + 1);
		$namespaceString = substr($fqn, 0, $lastSlashPosition);

		$file = new PhpFile();
		$file->setStrictTypes();

		$alias = $classString === substr(strrchr(BaseLoader::class, '\\'), 1) ? 'Loader' : null;
		$namespace = $file->addNamespace($namespaceString)
			->addUse(BaseLoader::class, $alias);

		$class = $namespace->addClass($classString)
			->setExtends(BaseLoader::class)
			->setFinal()
			->setComment('Generated by orisai/installer');

		$class->addProperty(self::LOADER_PROPERTY_SCHEMA, $schema)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setComment('@var mixed[]');

		$class->addProperty(self::LOADER_PROPERTY_SWITCHES, $switches)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setComment('@var bool[]');

		$class->addProperty(self::LOADER_PROPERTY_MODULES_META, $modulesMeta)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setComment('@var mixed[]');

		$loaderFilePath = $this->pathResolver->buildPathFromParts([
			$this->pathResolver->getRootDir(),
			$this->rootPackageConfiguration->getSchemaPath(),
			$loaderConfiguration->getFile(),
		]);
		$this->writer->write($loaderFilePath, $file);
	}

}
