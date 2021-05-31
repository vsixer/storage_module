<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once 'server/RemoteServer.php';
require_once 'server/LocalServer.php';
require_once 'server/NullServer.php';
require_once 'server/StorageServer.php';

use \App\Libraries\Storage\LocalServer;
use \App\Libraries\Storage\RemoteServer;
use \App\Libraries\Storage\NullServer;
use \App\Libraries\Storage\StorageServer;

class Storage_lib
{
  const STORAGE_TYPE_LOCAL  = 'local';
  const STORAGE_TYPE_REMOTE = 'remote';
  const STORAGE_TYPE_BACKUP = 'backup';

  private mixed $CI;
  private array $storageConfig;

  /**
   * RemoteStorage constructor.
   */
  public function __construct()
  {
    //region Get Codeigniter instance
    $this->CI = & get_instance();
    //endregion

    //region Define storage config
    $this->CI->load->config('storage_lib', true);

    $this->storageConfig = $this->CI->config->item('storage_lib');
    //endregion
  }

  /**
   * @param string     $sourceFilePath
   * @param string     $targetFilePath
   * @param array|null $listOfStorages
   *
   * @throws Exception
   */
  public function storeFile(
    string $sourceFilePath,
    string $targetFilePath,
    ?array $listOfStorages
  ) : void {
    //region Add default storage to list of storages if list is empty
    if (! $listOfStorages)
    {
      $listOfStorages = [$this->storageConfig['default_storage_type']];
    }
    //endregion

    foreach ($listOfStorages as $storageType)
    {
      //region Get storage of requested type
      $storage = $this->getStorageOfType($storageType);
      //endregion

      //region Store file
      $storage->store($sourceFilePath, $targetFilePath);
      //endregion
    }
  }

  public function deleteFile(
    string $filePath,
    ?array $listOfStorages
  ) : void {
    //region Define list of storages to remove file from
    if ( ! $listOfStorages)
    {
      $listOfStorages = [
        self::STORAGE_TYPE_LOCAL,
        self::STORAGE_TYPE_REMOTE,
        self::STORAGE_TYPE_BACKUP
      ];
    }
    //endregion

    foreach ($listOfStorages as $storageType)
    {
      //region Get storage of requested type
      $storage = $this->getStorageOfType($storageType);
      //endregion

      //region Delete file
      $storage->deleteFile($filePath);
      //endregion
    }
  }

  /**
   * @param string $storageType
   * @param string $sourcePath
   * @param string $targetPath
   *
   * @throws Exception
   */
  public function copyFile(
    string $storageType,
    string $sourcePath,
    string $targetPath
  ) : void
  {
    //region Get storage of requested type
    $storage = $this->getStorageOfType($storageType);
    //endregion

    //region Copy file
    $storage->copyFile($sourcePath, $targetPath);
    //endregion
  }

  /**
   * @param string      $filePath
   * @param string|null $preferredStorageType Will try to locate file at this storage first.
   * @param bool $checkFileExists Defines whether isFileExists method should be called or not.
   *                              If false, result url will be composed for storage provided at $preferredStorageType.
   *                              If no storage provided, will be used first one from the list defined by getStorageSearchOrder method.
   *
   * @return string
   */
  public function getFileUrl(
    string $filePath,
    string $preferredStorageType = null,
    bool $checkFileExists = true
  ) : string {
    //region Define storage search order
    $storageSearchOrder = $this->getStorageSearchOrder($preferredStorageType);
    //endregion

    //region Try to find file at storages in defined order
    foreach ($storageSearchOrder as $storageType)
    {
      $storage = $this->getStorageOfType($storageType);

      if ($checkFileExists)
      {
        if ( ! $storage->isFileExists($filePath))
        {
          continue;
        }
      }

      return $storage->getFileUrl($filePath);
    }
    //endregion

    //region Return empty string if file not found
    return '';
    //endregion
  }

  /**
   * @param string     $filePath
   * @param array|null $storagesToSearch
   *
   * @return bool
   */
  public function isFileExists(string $filePath, ?array $storagesToSearch = null) : bool
  {
    //region Define storage search order
    $storageSearchOrder = $storagesToSearch ?? $this->getStorageSearchOrder();
    //endregion

    //region Check file existence is all storages
    foreach ($storageSearchOrder as $storageType)
    {
      $storage = $this->getStorageOfType($storageType);

      if (! $storage->isFileExists($filePath))
      {
        continue;
      }

      return true;
    }
    //endregion

    return false;
  }

  //region Private methods
  /**
   * @param string $storageType
   *
   * @return StorageServer
   */
  private function getStorageOfType(string $storageType) : StorageServer
  {
    //region Local server
    if ($storageType == self::STORAGE_TYPE_LOCAL)
    {
      $serverConfig = $this->getStorageServerConfig('local');

      return new LocalServer($serverConfig);
    }
    //endregion

    //region Remote server
    if ($storageType == self::STORAGE_TYPE_REMOTE)
    {
      $serverConfig = $this->getStorageServerConfig('do/ipgimages');

      return new RemoteServer($serverConfig);
    }
    //endregion

    //region Backup server
    if ($storageType == self::STORAGE_TYPE_BACKUP)
    {
      $serverConfig = $this->getStorageServerConfig('wsb/cryper');

      return new RemoteServer($serverConfig);
    }
    //endregion

    return new NullServer();
  }

  /**
   * @param string $configGroupName
   *
   * @return array
   */
  private function getStorageServerConfig(string $configGroupName) : array
  {
    return $this->storageConfig['server'][$configGroupName] ?? [];
  }

  /**
   * @param string|null $preferredStorageType
   *
   * @return array
   */
  private function getStorageSearchOrder(?string $preferredStorageType = null) : array
  {
    $storageSearchOrder = [self::STORAGE_TYPE_LOCAL, self::STORAGE_TYPE_REMOTE];

    if ($preferredStorageType)
    {
      $storageSearchOrder = array_unique(
        [
          $preferredStorageType,
          ...$storageSearchOrder,
        ]
      );
    }

    return $storageSearchOrder;
  }
  //endregion
}
