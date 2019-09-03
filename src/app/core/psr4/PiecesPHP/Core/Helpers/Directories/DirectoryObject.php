<?php
/**
 * DirectoryObject.php
 */

namespace PiecesPHP\Core\Helpers\Directories;

/**
 * DirectoryObject - Representa un directorio
 *
 * @package     PiecesPHP\Core\Helpers\Directories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class DirectoryObject implements \JsonSerializable
{
	/**
	 * $root
	 *
	 * @var string
	 */
	protected $root = '';

	/**
	 * $rawPath
	 *
	 * @var string
	 */
	protected $rawPath = '';

	/**
	 * $path
	 *
	 * @var string
	 */
	protected $path = '';

	/**
	 * $basename
	 *
	 * @var string
	 */
	protected $basename = '';

	/**
	 * $exists
	 *
	 * @var bool
	 */
	protected $exists = false;

	/**
	 * $directories
	 *
	 * @var DirectoryObject[]
	 */
	protected $directories = [];

	/**
	 * $files
	 *
	 * @var FileObject[]
	 */
	protected $files = [];

	/**
	 * $ignoreDefault
	 *
	 * @var string[]
	 */
	protected $ignoreDefault = [
		'.',
		'..',
	];

	const CHMOD_LEVEL_ALL = -1;
	const CHMOD_LEVEL_CONTENT = 1;

	/**
	 * __construct
	 *
	 * @param string $path
	 * @param string $root
	 * @return static
	 */
	public function __construct(string $path, string $root = null)
	{
		$this->rawPath = $path;
		$this->path = realpath($this->rawPath);
		$this->exists = file_exists($this->rawPath);
		$this->basename = basename($this->rawPath);
		if (is_null($root)) {
			$this->root = str_replace($this->basename, '', $this->path);
		} else {
			$this->root = $root;
		}
	}

	/**
	 * process
	 *
	 * @param FilesIgnore $filesIgnore
	 * @param array $ignored_files
	 * @return $this
	 */
	public function process(FilesIgnore $filesIgnore = null, array &$ignored_files = [])
	{

		if ($this->directoryExists()) {

			$content = scandir($this->path);

			foreach ($content as $basename) {

				$path = $this->path . DIRECTORY_SEPARATOR . $basename;
				$read = !in_array($basename, $this->ignoreDefault);
				$relative_path = str_replace($this->root, '', $path);

				if ($read) {
					$ignore = !is_null($filesIgnore) ? $filesIgnore->ignore($relative_path) : false;
					if ($ignore) {
						$ignored_files[] = $relative_path;
						$read = false;
					}
				}

				if ($read) {
					if (is_dir($path)) {
						$subDir = new DirectoryObject($path, $this->root);
						$subDir->process($filesIgnore, $ignored_files);
						$this->directories[] = $subDir;
					} else {
						$this->files[] = new FileObject($path);
					}
				}
			}
		}

		sort($ignored_files);

		return $this;
	}

	/**
	 * copyTo
	 *
	 * @param string $destination
	 * @param bool $compress
	 * @return array
	 */
	public function copyTo(string $destination, bool $compress = true)
	{
		$result = [
			'files' => [],
			'folders' => [],
			'total_copies' => 0,
		];

		if ($compress) {

			if (!file_exists($destination)) {
				mkdir($destination, 0777, true);
			}

			if ($this->directoryExists()) {

				$zip = new \ZipArchive();
				$zip->open($destination . DIRECTORY_SEPARATOR . 'output.zip', \ZIPARCHIVE::CREATE);

				$this->fillZip($zip, $result);

				$zip->close();
			}
		} else {

			$destination = $destination . DIRECTORY_SEPARATOR . $this->basename;

			if (!file_exists($destination)) {
				mkdir($destination, 0777, true);
			}

			$result['folders'][] = [
				'from' => $this->getPath(),
				'to' => $destination,
			];

			if ($this->directoryExists()) {

				foreach ($this->files as $file) {

					$newPath = $destination . DIRECTORY_SEPARATOR . $file->getBasename();
					copy($file->getPath(), $newPath);
					chmod($newPath, 0777);

					$result['files'][] = [
						'from' => $file->getPath(),
						'to' => $newPath,
					];
				}

				foreach ($this->directories as $directory) {
					$sub_result = $directory->copyTo($destination, false);
					$result['files'] = array_merge($result['files'], $sub_result['files']);
					$result['folders'] = array_merge($result['folders'], $sub_result['folders']);
				}
			}
		}

		$result['total_copies'] = count($result['files']) + count($result['folders']);

		return $result;
	}


	/**
	 * delete
	 *
	 * @param mixed bool
	 * @return array
	 */
	public function delete(bool $deleteSelf = false)
	{
		$result = [
			'root' => $this->getPath(),
			'self_deleted' => false,
			'files' => [],
			'folders' => [],
			'total_deleted' => 0,
		];

		$all_content_deleted = true;

		if ($this->directoryExists()) {

			foreach ($this->files as $file) {
				$removed = unlink($file->getPath());
				$result['files'][] = [
					'path' => $file->getPath(),
					'deleted' => $removed,
				];
				$all_content_deleted = $all_content_deleted && $removed;
			}

			foreach ($this->directories as $directory) {
				$sub_result = $directory->delete(true);
				$removed = $sub_result['self_deleted'];
				$all_content_deleted = $all_content_deleted && $removed;
				if ($removed) {
					$result['folders'][] = [
						'path' => $directory->getPath(),
						'deleted' => $removed,
					];
				}
				$result['files'] = array_merge($result['files'], $sub_result['files']);
				$result['folders'] = array_merge($result['folders'], $sub_result['folders']);
			}
		}

		if ($deleteSelf) {
			if ($all_content_deleted) {
				$removed = rmdir($this->getPath());
				$result['self_deleted'] = $removed;
			}
		}

		$total_files_deleted = count(array_filter($result['files'], function ($e) {
			return $e['deleted'];
		}));
		$total_folders_deleted = count(array_filter($result['folders'], function ($e) {
			return $e['deleted'];
		}));

		$result['total_deleted'] = $total_files_deleted + $total_folders_deleted;

		return $result;
	}

	/**
	 * chmod
	 *
	 * @param int $mode
	 * @param int $levels Profundidad del cambio (-1 para recursividad total)
	 * @param bool $onlyDirectories
	 * @return array
	 */
	public function chmod(int $mode, int $levels = 0, bool $onlyDirectories = false)
	{
		/**
		 * @var array $result
		 * @var string[] $result['files']
		 * @var string[] $result['folders']
		 */
		$result = [
			'files' => [],
			'folders' => [],
		];

		if ($this->directoryExists()) {

			$result['folders'][] = [
				'path' => $this->path,
				'from' => mb_substr(sprintf('%o', fileperms($this->path)), -4),
				'to' => '0' . decoct($mode),
			];

			chmod($this->path, $mode);

			if ($levels > 0 || $levels == -1) {

				if (!$onlyDirectories) {

					foreach ($this->files as $file) {

						$filepath = $file->getPath();
						if (file_exists($filepath)) {
							$result['files'][] = [
								'path' => $filepath,
								'from' => mb_substr(sprintf('%o', fileperms($filepath)), -4),
								'to' => '0' . decoct($mode),
							];
							chmod($filepath, $mode);
						}
					}
				}

				foreach ($this->directories as $directory) {

					if ($directory->directoryExists()) {
						$levels = $levels > 0 ? $levels - 1 : $levels;
						$sub_result = $directory->chmod($mode, $levels, $onlyDirectories);
						$result['files'] = array_merge($result['files'], $sub_result['files']);
						$result['folders'] = array_merge($result['folders'], $sub_result['folders']);
					}
				}
			}
		}

		return $result;
	}

	/**
	 * directoryExists
	 *
	 * @return bool
	 */
	public function directoryExists()
	{
		$this->exists = file_exists($this->rawPath);
		return $this->exists;
	}

	/**
	 * getPath
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * getBasename
	 *
	 * @return string
	 */
	public function getBasename()
	{
		return $this->basename;
	}

	/**
	 * getDirectories
	 *
	 * @return DirectoryObject[]
	 */
	public function getDirectories()
	{
		return $this->directories;
	}

	/**
	 * getFiles
	 *
	 * @return FileObject[]
	 */
	public function getFiles()
	{
		return $this->files;
	}

	/**
	 * fillZip
	 *
	 * @param \ZipArchive &$zip
	 * @param array &$logger
	 * @param string $directoryBasename
	 * @return void
	 */
	protected function fillZip(\ZipArchive &$zip, array &$logger, string $directoryBasename = null)
	{
		foreach ($this->files as $file) {
			$basename = $file->getBasename();
			$localPath = is_null($directoryBasename) ? $basename : $directoryBasename . DIRECTORY_SEPARATOR . $basename;
			$zip->addFile($file->getPath(), $localPath);
			$logger['files'][] = [
				'from' => $file->getPath(),
				'to' => $localPath,
			];
		}
		foreach ($this->directories as $directory) {
			$basename = $directory->getBasename();
			$localPath = is_null($directoryBasename) ? $basename : $directoryBasename . DIRECTORY_SEPARATOR . $basename;
			$directory->fillZip($zip, $logger, $localPath);
			$logger['folders'][] = [
				'from' => $directory->getPath(),
				'to' => $localPath,
			];
		}
	}

	/**
	 * jsonSerialize
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return [
			'basename' => $this->getBasename(),
			'path' => $this->getPath(),
			'files' => $this->getFiles(),
			'directories' => $this->getDirectories(),
		];
	}
}
