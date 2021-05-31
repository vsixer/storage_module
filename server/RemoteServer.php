<?php namespace App\Libraries\Storage;

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . '../vendor/autoload.php';
require_once 'StorageServer.php';

use Aws\S3\S3Client;
use Exception;

class RemoteServer implements StorageServer
{
  private mixed    $CI;
  private array    $storageConfig;

  public function __construct(array $storageConfig)
  {
    //region Get Codeigniter instance
    $this->CI = & get_instance();
    //endregion

    //region Define storage config
    $this->storageConfig = $storageConfig;
    //endregion
  }

  //region Public Methods
  /**
   * @param string $filePath
   *
   * @return bool
   * @throws Exception
   */
  public function isFileExists(string $filePath) : bool
  {
    //region Initialize server connection
    $s3Client = $this->connectServer();
    //endregion

    //region Process variables
    $filePath = "{$this->storageConfig['files_leading_path']}/{$filePath}";
    //endregion

    //region Check if file exists
    try
    {
      $s3Client
        ->headObject(
          [
            'Bucket' => $this->storageConfig['s3']['bucket'],
            'Key'    => $filePath,
          ]
        );
    }
    catch (Exception)
    {
      return false;
    }
    //endregion

    return true;
  }

  /**
   * @param string      $sourceFilePath
   * @param string|null $targetFilePath
   *
   * @return void
   * @throws Exception
   */
  public function store(string $sourceFilePath, string $targetFilePath = null) : void {
    //region Initialize server connection
    $s3Client = $this->connectServer();
    //endregion

    //region Process variables
    $targetFilePath = $targetFilePath ?? $sourceFilePath;
    //endregion

    //region Try to upload file to remote storage
    try
    {
      $s3Client->putObject(
        [
          'Bucket'       => $this->storageConfig['s3']['bucket'],
          'Key'          => $this->storageConfig['files_leading_path'] . '/' . $targetFilePath,
          'SourceFile'   => $sourceFilePath,
          'StorageClass' => 'REDUCED_REDUNDANCY',
          'ACL'          => 'public-read',
        ]
      );
    }
    catch (Exception $e)
    {
      ipg_write_log($e, 'storage');

      throw new Exception('Failed to upload file to remote storage.');
    }
    //endregion
  }

  /**
   * @param string $filePath
   *
   * @return string
   * @throws Exception
   */
  public function getFileUrl(string $filePath) : string
  {
    //region Initialize server connection
    $s3Client = $this->connectServer();
    //endregion

    $filePath = "{$this->storageConfig['files_leading_path']}/{$filePath}";

    return $s3Client->getObjectUrl($this->storageConfig['s3']['bucket'], $filePath);
  }

  /**
   * @param string $filePath
   *
   * @throws Exception
   */
  public function deleteFile(string $filePath) : void
  {
    //region Initialize server connection
    $s3Client = $this->connectServer();
    //endregion

    @$s3Client->deleteObject(
      [
        'Bucket' => $this->storageConfig['s3']['bucket'],
        'Key'    => $this->storageConfig['files_leading_path'] . '/' . $filePath,
      ]
    );
  }

  /**
   * @param string $sourcePath
   * @param string $targetPath
   *
   * @throws Exception
   */
  public function copyFile(string $sourcePath, string $targetPath) : void
  {
    //region Initialize server connection
    $s3Client = $this->connectServer();
    //endregion

    //region Copy file
    try
    {
      $s3Client->copyObject(
        [
          'Bucket'     => $this->storageConfig['s3']['bucket'],
          'CopySource' => $this->storageConfig['files_leading_path'] . '/' . $sourcePath,
          'key'        => $this->storageConfig['files_leading_path'] . '/' . $targetPath,
        ]
      );
    }
    catch (Exception $e)
    {
      ipg_write_log($e, 'storage');

      throw new Exception('Failed to copy file at remote storage.');
    }
    //endregion
  }
  //endregion

  //region Private Methods
  /**
   * @return S3Client
   * @throws Exception
   */
  private function connectServer() : S3Client
  {
    return new S3Client($this->storageConfig['s3']);
  }
  //endregion
}
