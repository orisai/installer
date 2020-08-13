<?php declare(strict_types = 1);

namespace Orisai\Installer\Files;

use Composer\Downloader\FilesystemException;
use Nette\PhpGenerator\PhpFile;
use function file_put_contents;

final class Writer
{

	public function write(string $file, PhpFile $content): void
	{
		$written = file_put_contents($file, (string) $content);

		if ($written === false) {
			throw new FilesystemException(
				'An error occurred during writing of modules config file.',
			);
		}
	}

}
