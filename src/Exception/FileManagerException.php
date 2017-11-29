<?php
namespace Sellastica\FileManager\Exception;

class FileManagerException extends \Exception
{
	/**
	 * @param \Exception $e
	 * @return FileManagerException
	 */
	public static function fromException(\Exception $e)
	{
		return new self($e->getMessage(), $e->getCode(), $e);
	}
}
