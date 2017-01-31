<?php

namespace Arc\Filesystem;

class FileManager
{
    /**
     * Copy a file from the first path to the second path, overwriting any file that exists
     * already at that path
     *
     * @param string $fromPath
     * @param string $toPath
     **/
    public function copyOver($fromPath, $toPath)
    {
        if (file_exists($toPath)) {
            unlink($toPath);
        }
        return copy($fromPath, $toPath);
    }

    /**
     * Creates a directory at the given path if one does not already exist
     *
     * @param string $dirPath
     **/
    public function createDirectory($dirPath)
    {
        if (file_exists($dirPath)) {
            return;
        }
        mkdir($dirPath);
    }

    /**
     * Deletes any existing directory recursively at the given path and creates a fresh
     * one, creating any parent folders that do not already exist
     *
     * @param string $dirPath
     **/
    public function createFreshDirectory($dirPath)
    {
        $this->deleteDirectory($dirPath);

        $subfolders = explode('/', $dirPath);

        $path = "";

        foreach ($subfolders as $subfolder) {
            if ($subfolder == "") {
                continue;
            }
            $newPath = $path . "/" . $subfolder;
            $this->createDirectory($newPath);
            $path = $newPath;
        }
    }

    /**
     * Deletes the given directory and all files it contains recursively
     *
     * @param string $dirPath
     **/
    public function deleteDirectory($dirPath)
    {
        if (! is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::deleteDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }

    /**
     * Returns true if the given directory contains no files
     *
     * @param string $dirPath
     * @return bool
     **/
    public function directoryIsEmpty($dirPath)
    {
        return count($this->getAllFilesInDirectory($dirPath)) == 0;
    }

    /**
     * Get all the files in a given directory and return them as an array of File objects
     *
     * @param string $dirPath The full path to the directory
     * @return array
     **/
    public function getAllFilesInDirectory($dirPath)
    {
        return array_map(function($fileName) use ($dirPath) {
            return $this->getFile($dirPath . '/' . $fileName);
        }, preg_grep('/^([^.])/', scandir($dirPath)));
    }

    /**
     * Get the given file and return a File object
     *
     * @param string $filePath The full path to the file
     * @return array
     **/
    public function getFile($filePath)
    {
        return new File($filePath);
    }
}