<?php

namespace OKNManager\Libraries\Files;

use OKNManager\BM\Exceptions\FileNotSavedException;
use OKNManager\BM\Repositories\FilesRepository;

class FileManager
{


    /**
     * max number of retries of connection
     *
     * @var int
     */
    private $maxRetries;

    /**
     * The object with the file connector we are going to implement
     *
     * @var FileRepository The implementation of the connection with the metadata persistance system
     */
    private $fileRepository;

    /**
     * @var FileConnectorFactory The factory of data persistance connector implementations.
     */
    private $factory;


    public function __construct(FileConnectorFactory $fileFactoy, FilesRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
        $this->factory = $fileFactoy;
        $this->maxRetries =  env('FILE_PERSIST_NUMBER_TRIES');
    }

    /**
     * Save content to destination path
     *
     * @param string $path     The path to store the file (path + filename)
     * @param string $content  The content of the file
     * @param string $group    The file group wich the file belongs to.
     * @param string $type     The file type wich the file belongs to .
     * @param int    $personId The id of the user wich perform the action
     *
     * @throws FileNotSavedException If the file repository returns an exception
     *
     * @return array
     *  */
    public function saveFile(string $path, string $content, string $group, string $type, int $personId):array
    {
        $connector = $this->factory->build($type);

        try {
            $metaData = $this->prepareFileMetadata($path, $content, $group, $type, $personId);
            $connector->saveFile($path, $content, $metaData, $group);
            $savedFile = $this->fileRepository->save($metaData);

            return $savedFile;
        } catch (FileNotSavedException $e) {
            $this->rollBackSaveFile($connector, $path, $group);
            throw new FileNotSavedException($e->getMessage());
        }
    }

    /**
     * List all file in our repository
     *
     * @return array
     */
    public function listFiles():array
    {
        $files =  $this->fileRepository->all();

        return $files;
    }

    /**
     * Get contents of target file
     *
     * @param int $id the id of the file to get
     *
     * @return string
     */
    public function getFileContents(int $id):string
    {
        $metaData = $this->fileRepository->find($id);
        $fileType = $this->fileRepository->getFileTypeById($metaData['file_type_id']);
        $connector = $this->factory->build($fileType);
        $filePath = $this->fileRepository->getFilePathById($id);
        $fileGroup = $this->fileRepository->getFileGroupCodeById($metaData['file_group_id']);
        $fileContent = $connector->getFile($filePath, $fileGroup);

        return $fileContent;
    }

    /**
     * Get the metadata of a file given the id
     *
     * @param int $id The id of the file we want to get the metadata
     */
    public function getFileMetadata(int $id)
    {
        return $this->fileRepository->find($id);
    }

    /**
     * Update file content and/or metadata
     *
     * @param int    $id       The id of the file we want to update
     * @param string $content  The content of the file in case we want to update the content
     * @param int    $personId The id of the user who updates the file
     *
     * @return void
     */
    public function updateFile(int $id, string $content, int  $personId):void
    {
        $old_data = $this->fileRepository->find($id);
        $connector = $this->factory->build($this->fileRepository->getFileTypeById($old_data['file_type_id']));
        $metaData = $this->prepareFileMetadata($old_data['path'], $content, $old_data['file_group_id'], $old_data['file_type_id'], $personId);
        $connector->updateFile($old_data['path'], $content, $metaData, $this->fileRepository->getFileGroupCodeById($old_data['file_group_id']));
        $this->fileRepository->updateFile($id, $metaData, $personId);
    }

    /**
     * Delete a file given an id
     *
     *
     * @param int $id     The id of the file to delete
     * @param int $userId The id of the user who deleted the file
     *
     * @return void
     */
    public function deleteFile(int $id, int  $userId):void
    {
        $this->fileRepository->deleteFile($id, $userId);
    }

    /**
     * Function to rollback file saved in connector to use when saveFile fails saving metadata
     *
     * @param IFileConnector $connector
     * @param string         $path      The path of the saved file
     * @param string         $group     The
     *
     * @return void
     */
    public function rollBackSaveFile(IFileConnector $connector, string $path, string $group)
    {
        $connector->deleteFile($group, $path);
    }




    /**
     * Get the mime type of given file
     *
     * @param string $content The content of the file you are going to create
     *
     * @return string
     */
    private function getFileMimeType($content):string
    {
        $fileName = env('TMP_PATH').uniqid();
        \file_put_contents($fileName, $content);
        $mime = exec('file -b --mime-type  '.$fileName);
        unlink($fileName);

        return $mime;
    }

    /**
     * Get the size of given file
     *
     * @param string $content The content of the file to calculate it's size
     *
     * @return int
     */
    private function getFileSize($content):int
    {
        $fileName = env('TMP_PATH').uniqid();
        \file_put_contents($fileName, $content);
        $size = filesize($fileName);
        unlink($fileName);

        return $size;
    }

    /**
     * Prepare file metadata to persist
     *
     * @param string $path         Path to store the file
     * @param string $content      Content of the file
     * @param mixed  $group        File group of the file
     * @param mixed  $type         File type of the file
     * @param string $fileMimeType File mime type
     *
     * @return array
     */
    private function prepareFileMetadata(string $path, string $content, $group, $type, string $personId):array
    {
        $data = [];
        $data['filename'] = \pathinfo($path, PATHINFO_BASENAME);
        $data['path'] = $path;
        $data['user_id'] = $personId;
        $data['size'] = $this->getFileSize($content);
        $data['is_reachable'] = 1;
        $data['file_group_id'] = gettype($group) === 'string' ? $group : $this->fileRepository->getFileGroupCodeById($group);
        $data['file_type_id'] = gettype($type) === 'string' ? $type  : $this->fileRepository->getFileTypeById($type);
        $data['file_mime_type_id'] = $this->getFileMimeType($content);

        return $data;
    }
}
