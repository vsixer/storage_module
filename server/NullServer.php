<?php namespace App\Libraries\Storage;

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once 'StorageServer.php';

class NullServer implements StorageServer
{
  public function store(string $sourceFilePath, string $targetFilePath) : void
  {}

  public function isFileExists(string $filePath) : bool
  {
    return false;
  }

  public function getFileUrl(string $filePath) : string
  {
    return '';
  }

  public function deleteFile(string $filePath) : void {}

  public function copyFile(string $sourcePath, string $targetPath) : void {}
}
