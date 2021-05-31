<?php namespace App\Libraries\Storage;

if (!defined('BASEPATH')) exit('No direct script access allowed');

interface StorageServer
{
  public function store(string $sourceFilePath, string $targetFilePath) : void;
  public function isFileExists(string $filePath) : bool;
  public function getFileUrl(string $filePath) : string;
  public function deleteFile(string $filePath) : void;
  public function copyFile(string $sourcePath, string $targetPath) : void;
}
