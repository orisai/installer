<?php declare(strict_types = 1);

namespace Orisai\Installer\Loader;

use Nette\Neon\Neon;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\Utils\FileSystem;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
use Orisai\Installer\Console\InstallCommand;
use Orisai\Installer\Modules\Module;
use Orisai\Installer\Modules\Modules;
use Orisai\Installer\Schema\ConfigFilePriority;
use Orisai\Installer\Schema\ConfigFileSchema;
use Orisai\Installer\Schema\LoaderSchema;
use Orisai\Installer\SchemaName;
use Symfony\Component\Filesystem\Path;
use function array_keys;
use function array_merge;
use function assert;
use function class_exists;
use function dirname;
use function implode;
use function is_subclass_of;
use function sprintf;
use function str_replace;
use function strrchr;
use function strrpos;
use function substr;

/**
 * @internal
 */
final class LoaderGenerator
{

	private Modules $modules;

	public function __construct(Modules $modules)
	{
		$this->modules = $modules;
	}

	public function generateAndSave(): LoaderSchema
	{
		$loaderSchema = $this->modules->getRootModule()->getSchema()->getLoader();

		if ($loaderSchema === null) {
			$loaderSchema = new LoaderSchema(
				__DIR__ . '/DefaultLoader.php',
				DefaultLoader::class,
			);
		}

		$names = $this->getNamespaceAndClass($loaderSchema);
		$dependencies = $this->getDependencies();

		$file = $this->getFile(
			$names['namespace'],
			$names['class'],
			$dependencies['schema'],
			$dependencies['switches'],
			$dependencies['modules'],
		);

		FileSystem::write($loaderSchema->getFile(), (string) $file);

		FileSystem::write(
			dirname($loaderSchema->getFile()) . '/modules.stub.neon',
			Neon::encode($this->getStubFileContent($dependencies['modules']), true),
		);

		return $loaderSchema;
	}

	public function generate(): BaseLoader
	{
		$dependencies = $this->getDependencies();

		return new DynamicLoader(
			$dependencies['schema'],
			$dependencies['switches'],
			$dependencies['modules'],
		);
	}

	/**
	 * @return array{schema: array<int, mixed>, switches: array<string, bool>, modules: array<string, array{dir: string}>}
	 */
	private function getDependencies(): array
	{
		$modules = $this->modules->getModules();

		$switches = $this->getSwitches($modules);

		$modulesMeta = [];

		$itemsByPriority = [
			ConfigFilePriority::first()->name => [],
			ConfigFilePriority::normal()->name => [],
			ConfigFilePriority::last()->name => [],
		];

		foreach ($modules as $module) {
			$package = $module->getData();
			$packageName = str_replace('/', '_', $package->getName());
			$packageDirRelative = $package->getRelativePath();
			$packageSchema = $module->getSchema();

			$modulesMeta[$packageName] = [
				LoaderKey::MetaDir => $packageDirRelative,
			];

			foreach ($packageSchema->getConfigFiles() as $configFile) {
				// Skip configuration if required package is not installed
				foreach ($configFile->getRequiredPackages() as $requiredPackage) {
					if ($this->modules->getModule($requiredPackage) === null) {
						continue 2;
					}
				}

				$item = [
					LoaderKey::SchemaFile => Path::makeRelative(
						$configFile->getAbsolutePath(),
						$this->modules->getRootModule()->getData()->getAbsolutePath(),
					),
				];

				$itemSwitches = $this->getConfigSwitches($configFile, $switches, $module);

				if ($itemSwitches !== []) {
					$item[LoaderKey::SchemaSwitches] = $itemSwitches;
				}

				$itemsByPriority[$configFile->getPriority()->name][] = $item;
			}
		}

		$rootName = str_replace('/', '_', $this->modules->getRootModule()->getData()->getName());
		$modulesMeta['root'] = $modulesMeta[$rootName];
		unset($modulesMeta['__root__']);

		$schema = array_merge(
			$itemsByPriority[ConfigFilePriority::first()->name],
			$itemsByPriority[ConfigFilePriority::normal()->name],
			$itemsByPriority[ConfigFilePriority::last()->name],
		);

		return [
			'schema' => $schema,
			'switches' => $switches,
			'modules' => $modulesMeta,
		];
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
		$configSwitches = $configFile->getSwitches();
		$package = $module->getData();

		foreach ($configSwitches as $itemSwitchName => $itemSwitchValue) {
			if (!isset($switches[$itemSwitchName])) {
				$message = Message::create()
					->withContext(sprintf(
						'Trying to use switch `%s` for config file `%s` defined in `%s` of package `%s`.',
						$itemSwitchName,
						Path::makeRelative(
							$configFile->getAbsolutePath(),
							$this->modules->getRootModule()->getData()->getAbsolutePath(),
						),
						SchemaName::DefaultName,
						$package->getName(),
					))
					->withProblem(sprintf(
						'Switch is not defined by any of previously loaded `%s` schema files.',
						SchemaName::DefaultName,
					))
					->withSolution(sprintf(
						'Do not configure switch or define one or choose one of already loaded: `%s`',
						implode(', ', array_keys($switches)),
					));

				throw InvalidArgument::create()
					->withMessage($message);
			}
		}

		return $configSwitches;
	}

	/**
	 * @return array{namespace: string|null, class: string}
	 */
	private function getNamespaceAndClass(LoaderSchema $loaderSchema): array
	{
		$fqn = $loaderSchema->getClass();
		$this->validateLoader($fqn);

		$lastSlashPosition = strrpos($fqn, '\\');
		if ($lastSlashPosition === false) {
			$classString = $fqn;
			$namespaceString = null;
		} else {
			$classString = substr($fqn, $lastSlashPosition + 1);
			assert($classString !== false);
			$namespaceString = substr($fqn, 0, $lastSlashPosition);
			assert($namespaceString !== false);
		}

		return [
			'namespace' => $namespaceString,
			'class' => $classString,
		];
	}

	private function validateLoader(string $fqn): void
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
					InstallCommand::getDefaultName(),
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

		$class->addProperty('schema', $schema)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setType('array');

		$class->addProperty('switches', $switches)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setType('array');

		$class->addProperty('modules', $modulesMeta)
			->setVisibility(ClassType::VISIBILITY_PROTECTED)
			->setType('array');

		return $file;
	}

	/**
	 * @param array<string, array{dir: string}> $modules
	 * @return array<string, mixed>
	 */
	private function getStubFileContent(array $modules): array
	{
		foreach ($modules as &$module) {
			$module['dir'] = $module['dir'] === ''
				? '%rootDir%'
				: '%rootDir%/' . $module['dir'];
		}

		return [
			'parameters' => [
				'modules' => [
					$modules,
				],
			],
		];
	}

}
