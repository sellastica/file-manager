<?php
namespace Sellastica\FileManager\Model;

use Nette\Utils\Finder;
use Sellastica\Utils\Strings;

abstract class AbstractFileManager
{
	/**
	 * Trims / and \ from both sides of the string
	 * @param string $path
	 * @return string
	 */
	protected function trimSlashes(string $path): string
	{
		return Strings::trim($path, '/\\');
	}

	/**
	 * @param string $fileName
	 * @param array $files
	 * @return string
	 */
	protected function getUniqueFileName(string $fileName, array $files): string
	{
		$shortName = pathinfo($fileName, PATHINFO_FILENAME);
		$extension = pathinfo($fileName, PATHINFO_EXTENSION);

		if (in_array($fileName, $files)) {
			$i = 1;
			do {
				$name = "$shortName($i)";
				$i++;
			} while (in_array($fileName = "$name.$extension", $files));
		}

		return $fileName;
	}

	/**
	 * @param string $fileName
	 * @param string $path
	 * @return array
	 */
	protected function findFiles(string $fileName, string $path): array
	{
		$name = pathinfo($fileName, PATHINFO_FILENAME);
		$extension = pathinfo($fileName, PATHINFO_EXTENSION);

		$files = [];
		foreach (Finder::findFiles($name . '*.' . $extension)->from($path) as $splFileInfo) {
			$files[] = $splFileInfo->getFileName();
		};

		return $files;
	}
}
