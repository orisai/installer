<?php declare(strict_types = 1);

namespace Orisai\Installer\Loading;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\Utils\FileSystem;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
use Orisai\Installer\Console\GenerateLoaderCommand;
use Orisai\Installer\Data\InstallablePackageData;
use Orisai\Installer\Data\InstallerData;
use Orisai\Installer\Plugin;
use Orisai\Installer\Resolving\ModuleResolver;
use Orisai\Installer\Schema\ConfigFilePriority;
use Orisai\Installer\Schema\ConfigFileSchema;
use Orisai\Installer\Schema\LoaderSchema;
use Orisai\Installer\Utils\PluginActivator;
use ReflectionClass;
use Symfony\Component\Filesystem\Path;
use function array_keys;
use function array_merge;
use function class_exists;
use function implode;
use function is_subclass_of;
use function sprintf;
use function strrchr;
use function strrpos;
use function substr;

/**
 * @internal
 */
final class LoaderGenerator
{

	private const
		LOADER_PROPERTY_SCHEMA = 'schema',
		LOADER_PROPERTY_MODULES_META = 'modulesMeta',
		LOADER_PROPERTY_SWITCHES = 'switches';

	private InstallerData $data;

	public function __construct(InstallerData $data)
	{
		$this->data = $data;
	}

	public function generateLoader(): void
	{
		$loaderConfiguration = $this->data->getRootPackage()->getConfig()->getSchema()->getLoader();

		if ($loaderConfiguration === null) {
			throw InvalidState::create()
				->withMessage(sprintf(
					'Loader should be always available by this moment. Entry point should check if plugin is activated with \'%s\'',
					PluginActivator::class,
				));
		}

		$resolver = new ModuleResolver($this->data);

		$this->generateClass($loaderConfiguration, $resolver->getResolvedConfigurations());
	}

	/**
	 * @param array<InstallablePackageData> $packages
	 */
	private function generateClass(LoaderSchema $loaderSchema, array $packages): void
	{
		$switches = $this->getSwitches($packages);

		$modulesMeta = [];

		$itemsByPriority = [
			ConfigFilePriority::high()->name => [],
			ConfigFilePriority::normal()->name => [],
			ConfigFilePriority::low()->name => [],
		];

		foreach ($packages as $package) {
			$packageName = $package->getName();
			$packageDirRelative = $package->getRelativePath();
			$packageSchema = $package->getConfig()->getSchema();

			$modulesMeta[$packageName] = [
				BaseLoader::META_ITEM_DIR => $packageDirRelative,
			];

			foreach ($packageSchema->getConfigFiles() as $configFile) {
				// Skip configuration if required package is not installed
				foreach ($configFile->getRequiredPackages() as $requiredPackage) {
					if ($this->data->getPackage($requiredPackage) === null) {
						continue 2;
					}
				}

				$item = [
					BaseLoader::SCHEMA_ITEM_FILE => Path::makeRelative(
						$configFile->getFile(),
						$this->data->getRootDir(),
					),
				];

				$itemSwitches = $this->getConfigSwitches($configFile, $switches, $package);

				if ($itemSwitches !== []) {
					$item[BaseLoader::SCHEMA_ITEM_SWITCHES] = $itemSwitches;
				}

				$itemsByPriority[$configFile->getPriority()->name][] = $item;
			}
		}

		$schema = array_merge(
			$itemsByPriority[ConfigFilePriority::high()->name],
			$itemsByPriority[ConfigFilePriority::normal()->name],
			$itemsByPriority[ConfigFilePriority::low()->name],
		);

		$fqn = $loaderSchema->getClass();
		if ($this->isLoaderUpToDate($fqn, $schema, $modulesMeta, $switches)) {
			return;
		}

		$lastSlashPosition = strrpos($fqn, '\\');
		if ($lastSlashPosition === false) {
			$classString = $fqn;
			$namespaceString = null;
		} else {
			$classString = substr($fqn, $lastSlashPosition + 1);
			$namespaceString = substr($fqn, 0, $lastSlashPosition);
		}

		$this->writeFile(
			$loaderSchema->getFile(),
			$this->getFile($namespaceString, $classString, $schema, $switches, $modulesMeta),
		);
	}

	/**
	 * @param array<int, InstallablePackageData> $packages
	 * @return array<string, bool>
	 */
	private function getSwitches(array $packages): array
	{
		$switchesByPackage = [];
		foreach ($packages as $package) {
			$switchesByPackage[] = $package->getConfig()->getSchema()->getSwitches();
		}

		return array_merge(...$switchesByPackage);
	}

	/**
	 * @param array<string, bool> $switches
	 * @return array<string, bool>
	 */
	private function getConfigSwitches(
		ConfigFileSchema $configFile,
		array $switches,
		InstallablePackageData $package
	): array
	{
		$itemSwitches = $configFile->getRequiredSwitchValues();

		foreach ($itemSwitches as $itemSwitchName => $itemSwitchValue) {
			if (!isset($switches[$itemSwitchName])) {
				$message = Message::create()
					->withContext(sprintf(
						'Trying to use switch `%s` for config file `%s` defined in `%s` of package `%s`.',
						$itemSwitchName,
						Path::makeRelative(
							$configFile->getFile(),
							$this->data->getRootDir(),
						),
						$package->getConfig()->getSchemaFile(),
						$package->getName(),
					))
					->withProblem(sprintf(
						'Switch is not defined by any of previously loaded `%s` schema files.',
						Plugin::DEFAULT_FILE_NAME,
					))
					->withSolution(sprintf(
						'Do not configure switch or define one or choose one of already loaded: `%s`',
						implode(', ', array_keys($switches)),
					));

				throw InvalidArgument::create()
					->withMessage($message);
			}
		}

		return $itemSwitches;
	}

	/**
	 * @param array<int, mixed>    $schema
	 * @param array<string, mixed> $modulesMeta
	 * @param array<string, bool>  $switches
	 */
	public function isLoaderUpToDate(string $fqn, array $schema, array $modulesMeta, array $switches): bool
	{
		if (class_exists($fqn)) {
			if (!is_subclass_of($fqn, BaseLoader::class)) {
				$message = Message::create()
					->withContext('Generating configuration loader.')
					->withProblem(sprintf(
						'Loader class `%s` already exists but is not subclass of `%s`.',
						$fqn,
						BaseLoader::class,
					))
					->withSolution(sprintf(
						'Remove or rename existing class and then run command `composer %s`',
						GenerateLoaderCommand::getDefaultName(),
					));

				throw InvalidState::create()
					->withMessage($message);
			}

			$loaderReflection = new ReflectionClass($fqn);
			$loaderProperties = $loaderReflection->getDefaultProperties();

			if (
				$loaderProperties[self::LOADER_PROPERTY_SCHEMA] === $schema
				&& $loaderProperties[self::LOADER_PROPERTY_MODULES_META] === $modulesMeta
				&& $loaderProperties[self::LOADER_PROPERTY_SWITCHES] === $switches
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<int, mixed>    $schema
	 * @param array<string, bool>  $switches
	 * @param array<string, mixed> $modulesMeta
	 */
	private function getFile(
		?string $namespaceString,
		string $classString,
		array $schema,
		array $switches,
		array $modulesMeta
	): PhpFile
	{
		$file = new PhpFile();
		$file->setStrictTypes();

		if ($namespaceString === null) {
			$class = $file->addClass($classString);
		} else {
			$alias = $classString === substr(strrchr(BaseLoader::class, '\\'), 1) ? 'Loader' : null;
			$namespace = $file->addNamespace($namespaceString)
				->addUse(BaseLoader::class, $alias);
			$class = $namespace->addClass($classString);
		}

		$class->setExtends(BaseLoader::class)
			->setFinal()
			->setComment('Generated by orisai/installer');

		$class->addProperty(self::LOADER_PROPERTY_SCHEMA, $schema)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setType('array')
			->setComment('{@inheritdoc}');

		$class->addProperty(self::LOADER_PROPERTY_SWITCHES, $switches)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setType('array')
			->setComment('{@inheritdoc}');

		$class->addProperty(self::LOADER_PROPERTY_MODULES_META, $modulesMeta)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setType('array')
			->setComment('{@inheritdoc}');

		return $file;
	}

	private function writeFile(string $path, PhpFile $file): void
	{
		FileSystem::write($path, (string) $file);
	}

}
