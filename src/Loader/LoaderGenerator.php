<?php declare(strict_types = 1);

namespace Orisai\Installer\Loader;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\Utils\FileSystem;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
use Orisai\Installer\Console\GenerateLoaderCommand;
use Orisai\Installer\Modules\Module;
use Orisai\Installer\Modules\Modules;
use Orisai\Installer\Plugin;
use Orisai\Installer\Schema\ConfigFilePriority;
use Orisai\Installer\Schema\ConfigFileSchema;
use Orisai\Installer\Schema\LoaderSchema;
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

	private Modules $modules;

	public function __construct(Modules $modules)
	{
		$this->modules = $modules;
	}

	public function generateLoader(): void
	{
		$loaderSchema = $this->modules->getRootModule()->getSchema()->getLoader();

		if ($loaderSchema === null) {
			$loaderSchema = new LoaderSchema(
				__DIR__ . '/DefaultLoader.php',
				DefaultLoader::class,
			);
		}

		$this->generateClass($loaderSchema);
	}

	private function generateClass(LoaderSchema $loaderSchema): void
	{
		$modules = $this->modules->getModules();

		$switches = $this->getSwitches($modules);

		$modulesMeta = [];

		$itemsByPriority = [
			ConfigFilePriority::high()->name => [],
			ConfigFilePriority::normal()->name => [],
			ConfigFilePriority::low()->name => [],
		];

		foreach ($modules as $module) {
			$package = $module->getData();
			$packageName = $package->getName();
			$packageDirRelative = $package->getRelativePath();
			$packageSchema = $module->getSchema();

			$modulesMeta[$packageName] = [
				BaseLoader::META_ITEM_DIR => $packageDirRelative,
			];

			foreach ($packageSchema->getConfigFiles() as $configFile) {
				// Skip configuration if required package is not installed
				foreach ($configFile->getRequiredPackages() as $requiredPackage) {
					if ($this->modules->getModule($requiredPackage) === null) {
						continue 2;
					}
				}

				$item = [
					BaseLoader::SCHEMA_ITEM_FILE => Path::makeRelative(
						$configFile->getFile(),
						$this->modules->getRootDir(),
					),
				];

				$itemSwitches = $this->getConfigSwitches($configFile, $switches, $module);

				if ($itemSwitches !== []) {
					$item[BaseLoader::SCHEMA_ITEM_SWITCHES] = $itemSwitches;
				}

				$itemsByPriority[$configFile->getPriority()->name][] = $item;
			}
		}

		$modulesMeta['__root__'] = $modulesMeta[$this->modules->getRootModule()->getData()->getName()];

		$schema = array_merge(
			$itemsByPriority[ConfigFilePriority::high()->name],
			$itemsByPriority[ConfigFilePriority::normal()->name],
			$itemsByPriority[ConfigFilePriority::low()->name],
		);

		$fqn = $loaderSchema->getClass();
		$this->validateLoader($fqn);

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
	 * @param array<string, Module> $modules
	 * @return array<string, bool>
	 */
	private function getSwitches(array $modules): array
	{
		$switchesByPackage = [];
		foreach ($modules as $module) {
			$switchesByPackage[] = $module->getSchema()->getSwitches();
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
		Module $module
	): array
	{
		$itemSwitches = $configFile->getRequiredSwitchValues();
		$package = $module->getData();

		foreach ($itemSwitches as $itemSwitchName => $itemSwitchValue) {
			if (!isset($switches[$itemSwitchName])) {
				$message = Message::create()
					->withContext(sprintf(
						'Trying to use switch `%s` for config file `%s` defined in `%s` of package `%s`.',
						$itemSwitchName,
						Path::makeRelative(
							$configFile->getFile(),
							$this->modules->getRootDir(),
						),
						$module->getSchemaRelativeName(),
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

	public function validateLoader(string $fqn): void
	{
		if (class_exists($fqn) && !is_subclass_of($fqn, BaseLoader::class)) {
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
		$file->addComment('phpcs:disable');

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
