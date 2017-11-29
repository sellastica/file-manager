<?php
namespace Sellastica\FileManager\Model;

use Nette\Http\FileUpload;
use Sellastica\FileManager\Exception\FileManagerException;
use Sellastica\FileManager\Exception\FileNotFoundException;

interface IFileManager
{
	/**
	 * @param FileUpload $file
	 * @param string $destination
	 * @param string|null $fileName If defined, it will not sanitize it and also not check for file name uniqueness
	 * @return Response
	 */
	function upload(FileUpload $file, string $destination, string $fileName = null): Response;

	/**
	 * @param string $url
	 * @param string $destination
	 * @param string|null $fileName
	 * @param bool $checkIfImage
	 * @return Response
	 */
	function download(string $url, string $destination, string $fileName = null, bool $checkIfImage = false): Response;

	/**
	 * @param string $string
	 * @param string $destination
	 * @param string $fileName
	 * @return Response
	 * @throws FileManagerException
	 */
	function fromString(string $string, string $destination, string $fileName): Response;

	/**
	 * @param string $base64
	 * @param string $destination
	 * @param string $fileName
	 * @return Response
	 */
	function fromBase64(string $base64, string $destination, string $fileName): Response;

	/**
	 * @param string $destination
	 * @param string $fileName
	 * @return Response
	 * @throws FileNotFoundException
	 */
	function remove(string $destination, string $fileName): Response;
}
