<?php declare(strict_types = 1);

namespace Orisai\Installer\Modules;

use Orisai\Installer\Schema\ModuleSchema;

/**
 * @internal
 */
final class ModuleSchemaMerger
{

	public function merge(ModuleSchema $parent, ModuleSchema $child): ModuleSchema
	{
		$schema = new ModuleSchema();
		$this->apply($schema, $parent);
		$this->apply($schema, $child);

		$loader = $child->getLoader();
		if ($loader !== null) {
			$schema->setLoader($loader->getFile(), $loader->getClass());
		}

		return $schema;
	}

	private function apply(ModuleSchema $new, ModuleSchema $merged): void
	{
		foreach ($merged->getConfigFiles() as $file) {
			$newFile = $new->addConfigFile($file->getAbsolutePath());
			$newFile->setPriority($file->getPriority());
			foreach ($file->getRequiredPackages() as $package) {
				$newFile->addRequiredPackage($package);
			}

			foreach ($file->getSwitches() as $name => $value) {
				if ($value) {
					$newFile->addRequiredSwitch($name);
				} else {
					$newFile->addForbiddenSwitch($name);
				}
			}
		}

		foreach ($merged->getSwitches() as $name => $value) {
			$new->addSwitch($name, $value);
		}
	}

}
