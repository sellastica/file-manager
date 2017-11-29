<?php
namespace Sellastica\FileManager\Model;

use Nette;
use Nette\Http\FileUpload;
use Sellastica\FileManager\Exception\FileManagerException;
use Sellastica\FileManager\Exception\FileNotFoundException;
use Sellastica\Monolog\Logger;
use Sellastica\Thumbnailer\HttpFileResolver;
use Sellastica\Thumbnailer\IResourceUrlResolver;
use Sellastica\Thumbnailer\LocalFileResolver;
use Sellastica\Utils\Files;

class LocalFileManager extends AbstractFileManager implements IFileManager
{
	/** @var Logger */
	private $logger;
	/** @var Nette\Http\Request */
	private $request;
	/** @var IResourceUrlResolver[] */
	private $resourceResolvers = [];

	/**
	 * @param Nette\Http\Request $request
	 * @param Logger $logger
	 */
	public function __construct(Nette\Http\Request $request, Logger $logger)
	{
		$this->request = $request;
		$this->logger = $logger;
		$this->resourceResolvers = [
			new LocalFileResolver($this->request),
			new HttpFileResolver($this->request),
		];
	}

	/**
	 * @param FileUpload $file
	 * @param string $destination
	 * @param string|null $fileName If defined, it will not sanitize it and also not check for file name uniqueness
	 * @return Response
	 * @throws FileManagerException
	 */
	public function upload(FileUpload $file, string $destination, string $fileName = null): Response
	{
		$originalFileName = $fileName ?: $file->getName();
		$destination = $this->trimSlashes($destination);
		$this->mkdir($destination);
		$sanitizedFileName = $fileName ?? Files::sanitizeFileName($originalFileName);

		try {
			if (!isset($fileName)) {
				$sanitizedFileName = $this->getUniqueFileName(
					$sanitizedFileName, $this->findFiles($sanitizedFileName, $this->getAbsolutePath($destination))
				);
			}

			$destination .= '/' . $sanitizedFileName;
			$file->move($this->getAbsolutePath($destination));
		} catch (Nette\InvalidStateException $e) {
			$this->logger->exception($e);
			throw FileManagerException::fromException($e);
		}

		return Response::uploadSuccess(
			$this->getUrl($destination), $sanitizedFileName, $originalFileName
		);
	}

	/**
	 * @param string $url
	 * @param string $destination
	 * @param string|null $fileName
	 * @param bool $checkIfImage
	 * @return Response
	 * @throws FileManagerException
	 */
	public function download(
		string $url,
		string $destination,
		string $fileName = null,
		bool $checkIfImage = false
	): Response
	{
		$originalFileName = $fileName ?: Nette\Utils\Strings::after($url, '/', -1);
		$destination = $this->trimSlashes($destination);
		$this->mkdir($destination);
		$fileName = Files::sanitizeFileName($originalFileName);

		try {
			//create unique file name
			$fileName = $this->getUniqueFileName(
				$fileName, $this->findFiles($fileName, $this->getAbsolutePath($destination))
			);
		} catch (Nette\InvalidStateException $e) {
			$this->logger->exception($e);
			throw FileManagerException::fromException($e);
		}

		$destination .= '/' . $fileName;
		foreach ($this->resourceResolvers as $resolver) {
			if ($resolver->match($url) && $resolver->exists($url)) {
				@copy($resolver->getSrc($url), $destination);
				@chmod($destination, 0666); // @ - possible low permission to chmod
				if ($checkIfImage) {
					if (!in_array(exif_imagetype($destination), [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
						@unlink($destination);
						throw new FileManagerException("File $url is not proper image type");
					}
				}

				return Response::uploadSuccess(
					$this->getUrl($destination), $fileName, $originalFileName
				);
			}
		}

		throw new FileManagerException("Download file $url failed");
	}

	/**
	 * @param string $string
	 * @param string $destination
	 * @param string $fileName
	 * @return Response
	 * @throws FileManagerException
	 */
	public function fromString(string $string, string $destination, string $fileName): Response
	{
		$originalFileName = $fileName;
		$destination = $this->trimSlashes($destination);
		$this->mkdir($destination);
		$fileName = Files::sanitizeFileName($fileName);

		try {
			$fileName = $this->getUniqueFileName($fileName, $this->findFiles($fileName, $this->getAbsolutePath($destination)));
			$destination .= '/' . $fileName;
			file_put_contents($this->getAbsolutePath($destination), $string);
		} catch (Nette\InvalidStateException $e) {
			$this->logger->exception($e);
			throw FileManagerException::fromException($e);
		}

		return Response::uploadSuccess(
			$this->getUrl($destination), $fileName, $originalFileName
		);
	}

	/**
	 * @param string $base64
	 * @param string $destination
	 * @param string $fileName
	 * @return Response
	 * @throws FileManagerException
	 */
	public function fromBase64(string $base64, string $destination, string $fileName): Response
	{
		return $this->fromString(
			base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64)), $destination, $fileName
		);
	}

	/**
	 * @param string $destination
	 * @param string $fileName
	 * @return Response
	 * @throws FileNotFoundException
	 */
	public function remove(string $destination, string $fileName): Response
	{
		$path = WWW_DIR . '/' . $this->trimSlashes($destination) . '/' . $fileName;
		if (is_file($path)) {
			@unlink($path);
			return Response::success();
		} else {
			throw new FileNotFoundException(sprintf('File %s does not exist', $path));
		}
	}

	/**
	 * @param string $path
	 * @return string
	 */
	private function getAbsolutePath(string $path)
	{
		return WWW_DIR . '/' . $path;
	}

	/**
	 * @param string $destination
	 * @return Nette\Http\UrlScript
	 */
	private function getUrl(string $destination)
	{
		$url = $this->request->getUrl();
		$url->setQuery(null);
		$url->setPath('/' . $destination);

		return $url;
	}

	/**
	 * @param string $destination
	 */
	private function mkdir(string $destination)
	{
		@mkdir($this->getAbsolutePath($destination), 0755, true); //@ - dir may already exist
	}
}
