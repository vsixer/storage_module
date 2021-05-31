<?php namespace App\Libraries\Storage;

use Exception;
use JetBrains\PhpStorm\Pure;

use function PHPUnit\Framework\throwException;

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once 'StorageServer.php';

class LocalServer implements StorageServer
{
  private mixed $CI;
  private array $storageConfig;

  public function __construct(array $storageConfig)
  {
    //region Get Codeigniter instance
    $this->CI = & get_instance();
    //endregion

    //region Define storage config
    $this->storageConfig = $storageConfig;
    //endregion
  }

  /**
   * @param string $sourceFilePath
   * @param string $targetFilePath
   *
   * @throws Exception
   */
  public function store(string $sourceFilePath, string $targetFilePathRelative) : void
  {
    $targetFilePathAbsolute = APPPATH . '../' . ltrim($targetFilePathRelative, '/');

    if (! copy($sourceFilePath, $targetFilePathAbsolute))
    {
      throw new Exception('Failed to upload file to local storage.');
    }
  }

  #[Pure] public function isFileExists(string $filePath) : bool
  {
    $filePath = ltrim($filePath, '/');

    $filePathAbsolute = APPPATH . '../' . $filePath;

    return is_file($filePathAbsolute);
  }

  public function getFileUrl(string $filePath) : string
  {
    return $this->CI->config->base_url($filePath);
  }

  public function deleteFile(string $filePath) : void
  {
    @unlink($filePath);
  }

  /**
   * @throws Exception
   */
  public function copyFile(string $sourcePath, string $targetPath) : void
  {
    if (! copy($sourcePath, $targetPath))
    {
      ipg_write_log("Error while copy file(local server) from: {$sourcePath} to: {$targetPath}", 'storage');

      throw new Exception('Unable to copy file at local server');
    }
  }
}
