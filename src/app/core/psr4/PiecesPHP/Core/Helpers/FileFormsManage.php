<?php

/**
 * FileFormsManage.php
 */
namespace PiecesPHP\Core\Helpers;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Forms\FilesHandler;

/**
 * FileFormsManage.
 *
 * Helper para creación, eliminación y obtención de archivos desde un formulario con interacción con tablas.
 *
 * @package     App\Helpers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class FileFormsManage
{
    /**
     * $table
     *
     * @var string
     */
    protected $table = '';
    /**
     * $fileColumnName
     *
     * @var string
     */
    protected $fileColumnName = '';
    /**
     * $identifierColumunName
     *
     * @var string
     */
    protected $identifierColumunName = '';
    /**
     * $handler
     *
     * @var FilesHandler
     */
    protected $handler;
    /**
     * $directory
     *
     * @var string
     */
    protected $directory = '';
    /**
     * $model
     *
     * @var BaseModel
     */
    protected $model;

    /**
     * __construct
     *
     * @param string $table
     * @param string $fileColumnName
     * @param FilesHandler $handler
     * @param string $directory
     * @return static
     */
    public function __construct(string $table, string $fileColumnName, string $identifierColumunName, FilesHandler $handler, string $directory = '')
    {
        $this->table = $table;
        $this->fileColumnName = $fileColumnName;
        $this->identifierColumunName = $identifierColumunName;
        $this->handler = $handler;
        $this->directory = $directory;

        $this->model = new BaseModel();
        $this->model->setTable($this->table);
    }

    /**
     * set
     *
     * @param mixed $identifierValue
     * @param array $otherColumnsValues
     * @param bool $overwrite
     * @return bool
     */
    public function set($identifierValue, array $otherColumnsValues = [], bool $overwrite = true)
    {
        $group_files = $this->handler->moveFilesTo($this->directory, true, $overwrite);
        $current_group_files = (array) $this->get($identifierValue);

        $group_files = $this->formattingUploadFiles($group_files);
        $group_files = $this->formattingUploadFilesUpdate($current_group_files, $group_files);
        $group_files = json_encode($group_files);

        $data = [
            $this->fileColumnName => $group_files,
        ];

        foreach ($otherColumnsValues as $column => $value) {
            if (is_string($column) && is_scalar($value)) {
                if (!isset($data[$column])) {
                    $data[$column] = $value;
                }
            }
        }
        $this->model->resetAll();
        $this->model->update($data)->where([
            $this->identifierColumunName => $identifierValue,
        ]);
        $updated = $this->model->execute();
        return $updated;

    }
    /**
     * get
     *
     * @param mixed $identifierValue
     * @return stdClass
     */
    public function get($identifierValue): \stdClass
    {
        $this->model->select()->where([
            $this->identifierColumunName => $identifierValue,
        ])->execute();
        $result = $this->model->result();
        $column = $this->fileColumnName;
        $has_record = count($result) > 0;
        if ($has_record) {
            $files = json_decode($result[0]->$column, true);
            $has_files = count($files) > 0;
            if ($has_files) {
                $result = (object) $files;
            } else {
                $result = new \stdClass();
            }
        } else {
            $result = new \stdClass();
        }
        return $result;
    }

    /**
     * delete
     *
     * @param string $fileIdentifier
     * @param mixed $identifierValue
     * @return bool
     */
    public function delete(string $fileIdentifier, $identifierValue): bool
    {
        $success = false;
        $group_files = (array) $this->get($identifierValue);

        foreach ($group_files as $name => $files) {
            if (array_key_exists($fileIdentifier, $files)) {
                $file_to_delete = $files[$fileIdentifier];
                $path = basepath($file_to_delete['path']);
                $exists = file_exists($path);
                if ($exists) {
                    unlink($path);
                    unset($group_files[$name][$fileIdentifier]);
                }
            }
        }

        $group_files = json_encode($group_files);

        $data = [
            $this->fileColumnName => $group_files,
        ];

        $this->model->resetAll();

        $this->model->update($data)->where([
            $this->identifierColumunName => $identifierValue,
        ]);

        return $this->model->execute();
    }

    /**
     * setDirectory
     *
     * @param string $directory
     * @return static
     */
    public function setDirectory(string $directory)
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * setHandler
     *
     * @param FilesHandler $handler
     * @return static
     */
    public function setHandler(FilesHandler $handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * formattingUploadFiles
     *
     * @param array $group_files
     * @return array
     */
    private function formattingUploadFiles(array $group_files)
    {
        foreach ($group_files as $name => $files) {
            foreach ($files as $index => $path) {
                unset($group_files[$name][$index]);
                $group_files[$name][uniqid()] = [
                    'path' => $path,
                    'date' => date('Y-m-d h:i:s'),
                ];
            }
        }
        return $group_files;
    }

    /**
     * formattingUploadFilesUpdate
     *
     * @param array $group_files
     * @param array $new_group_files
     * @return array
     */
    private function formattingUploadFilesUpdate(array $group_files, array $new_group_files)
    {
        foreach ($group_files as $name => $current_files) {
            if (array_key_exists($name, $new_group_files)) {
                $input = $new_group_files[$name];
                $merge = array_merge($input, $current_files);
                $new_group_files[$name] = $merge;
            } else {
                $new_group_files[$name] = $current_files;
            }
        }
        return $new_group_files;
    }
}
