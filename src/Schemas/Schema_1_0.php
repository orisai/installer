<?php declare(strict_types = 1);

namespace Orisai\Installer\Schemas;

use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Orisai\Installer\Config\FileConfig;
use Orisai\Installer\Config\LoaderConfig;
use Orisai\Installer\Config\PackageConfig;
use Orisai\Installer\Config\SimulatedModuleConfig;

final class Schema_1_0 implements Schema
{

	public function getStructure(): Structure
	{
		return Expect::structure([
			PackageConfig::VERSION_OPTION => Expect::anyOf(self::VERSION_1_0),
			PackageConfig::LOADER_OPTION => Expect::anyOf(
				Expect::null(),
				Expect::structure([
					LoaderConfig::FILE_OPTION => Expect::string()->required(),
					LoaderConfig::CLASS_OPTION => Expect::string()->required(),
				])->castTo('array')
			),
			PackageConfig::CONFIGS_OPTION => Expect::listOf(Expect::anyOf(
				Expect::string(),
				Expect::structure([
					FileConfig::FILE_OPTION => Expect::string()->required(),
					FileConfig::SWITCHES_OPTION => Expect::arrayOf(
						Expect::bool()
					),
					FileConfig::PACKAGES_OPTION => Expect::listOf(
						Expect::string()
					),
					FileConfig::PRIORITY_OPTION => Expect::anyOf(...FileConfig::PRIORITIES)
						->default(FileConfig::PRIORITY_DEFAULT),
				])->castTo('array')
			)),
			PackageConfig::SWITCHES_OPTION => Expect::arrayOf(
				Expect::bool()
			),
			PackageConfig::IGNORE_OPTION => Expect::listOf(
				Expect::string()
			),
			PackageConfig::SIMULATED_MODULES_OPTION => Expect::arrayOf(Expect::anyOf(
				Expect::string(),
				Expect::structure([
					SimulatedModuleConfig::PATH_OPTION => Expect::string()->required(),
					SimulatedModuleConfig::OPTIONAL_OPTION => Expect::bool(SimulatedModuleConfig::OPTIONAL_DEFAULT),
				])->castTo('array')
			)),
		])->castTo('array');
	}

}
