<?php
namespace Sellastica\FileManager\Model;

use Nette\Http\Url;

class Response
{
	/** @var Url|null */
	private $url;
	/** @var string|null */
	private $fileName;
	/** @var null|string */
	private $originalFileName;


	/**
	 * @param Url|null $url
	 * @param string $fileName
	 * @param string|null $originalFileName
	 * @internal param string $message
	 */
	private function __construct(
		Url $url = null,
		string $fileName = null,
		string $originalFileName = null
	)
	{
		$this->url = $url;
		$this->fileName = $fileName;
		$this->originalFileName = $originalFileName;
	}

	/**
	 * @return Url|null
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string|null
	 */
	public function getFileName()
	{
		return $this->fileName;
	}

	/**
	 * @return null|string
	 */
	public function getOriginalFileName(): ?string
	{
		return $this->originalFileName;
	}

	/**
	 * @param Url $url
	 * @param string $fileName
	 * @param string $originalFileName
	 * @return Response
	 */
	public static function uploadSuccess(Url $url, string $fileName, string $originalFileName): self
	{
		return new self($url, $fileName, $originalFileName);
	}

	/**
	 * @return Response
	 */
	public static function success(): self
	{
		return new self();
	}
}
