<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Enhanced Upload Class
 * 
 * Provides a modern, fluent interface for file uploads with support for:
 * - Multiple file uploads
 * - Better error handling
 * - Chainable configuration
 * - Detailed upload results
 * 
 * @package CodeIgniter
 * @subpackage Libraries
 * @category    Uploads
 * @author	WebbyPHP Team
 * @since	Version 3.0.0
 */
class Uploader
{

    /**
     * CI Singleton
     *
     * @var	object
     */
    protected $CI;

    /**
     * Configuration array
     *
     * @var	array
     */
    protected $config = [];

    /**
     * Results array
     *
     * @var	array
     */
    protected $results = [];

    /**
     * Errors array
     *
     * @var	array
     */
    protected $errors = [];

    /**
     * Uploaded files array
     *
     * @var	array
     */
    protected $uploadedFiles = [];

    // Default configuration
    protected $defaultConfig = [
        'upload_path'      => './uploads/',
        'allowed_types'    => 'gif|jpg|png',
        'max_size'         => 2048, // 2MB
        'max_width'        => 1024,
        'max_height'       => 768,
        'encrypt_name'     => false,
        'remove_spaces'    => true,
        'overwrite'        => false,
        'max_filename'     => 0,
        'file_ext_tolower' => false
    ];

    public function __construct($config = [])
    {
        $this->CI = get_instance();
        $this->config = array_merge($this->defaultConfig, $config);

        // Ensure upload directory exists
        if (!is_dir($this->config['upload_path'])) {
            if (!mkdir($this->config['upload_path'], 0755, true)) {
                throw new Exception("Cannot create upload directory: " . $this->config['upload_path']);
            }
        }

        log_message('info', 'Enhanced Upload Class Initialized');
    }

    /**
     * Fluent configuration methods
     */
    public function path($path)
    {
        $this->config['upload_path'] = rtrim($path, '/') . '/';
        return $this;
    }

    public function allowedTypes($types)
    {
        $this->config['allowed_types'] = is_array($types) ? implode('|', $types) : $types;
        return $this;
    }

    public function maxSize($size)
    {
        $this->config['max_size'] = $size;
        return $this;
    }

    public function maxDimensions($width, $height = null)
    {
        $this->config['max_width'] = $width;
        $this->config['max_height'] = $height ?: $width;
        return $this;
    }

    public function encryptNames($encrypt = true)
    {
        $this->config['encrypt_name'] = $encrypt;
        return $this;
    }

    public function overwrite($allow = true)
    {
        $this->config['overwrite'] = $allow;
        return $this;
    }

    /**
     * Upload single or multiple files
     * 
     * @param string|array $field_name Field name(s) from form
     * @return UploadResult
     */
    public function upload($fieldName = null)
    {
        $this->reset();

        // If no field specified, try to detect from $_FILES
        if ($fieldName === null) {
            $fieldName = array_keys($_FILES);
        }

        // Ensure we have an array
        $fields = is_array($fieldName) ? $fieldName : [$fieldName];

        foreach ($fields as $field) {
            $this->processField($field);
        }

        return new UploadResult($this->uploadedFiles, $this->errors);
    }

    /**
     * Process a single form field (which may contain multiple files)
     */
    protected function processField($fieldName)
    {
        if (!isset($_FILES[$fieldName])) {
            $this->errors[] = "No file found for field: {$fieldName}";
            return;
        }

        $files = $this->normalizeFiles($_FILES[$fieldName]);

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip empty file inputs
            }

            $this->processSingleFile($file, $fieldName);
        }
    }

    /**
     * Normalize $_FILES array to handle both single and multiple uploads
     */
    protected function normalizeFiles($files)
    {
        $normalized = [];

        if (is_array($files['name'])) {
            // Multiple files
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $normalized[] = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i]
                ];
            }
        } else {
            // Single file
            $normalized[] = $files;
        }

        return $normalized;
    }

    /**
     * Process a single file upload
     */
    protected function processSingleFile($file, $fieldName)
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadError($file['error'], $file['name']);
            return;
        }

        // Validate file
        $validation_result = $this->validateFile($file);
        if (!$validation_result['valid']) {
            $this->errors[] = $validation_result['error'];
            return;
        }

        // Generate filename
        $filename = $this->generateFilename($file['name']);
        $filepath = $this->config['upload_path'] . $filename;

        // Check if file exists and handle accordingly
        if (file_exists($filepath) && !$this->config['overwrite']) {
            $filename = $this->generateUniqueFilename($filename);
            $filepath = $this->config['upload_path'] . $filename;
        }

        // // Move uploaded file
        // if (move_uploaded_file($file['tmp_name'], $filepath)) {
        //     $this->uploadedFiles[] = [
        //         'field_name'    => $field_name,
        //         'original_name' => $file['name'],
        //         'file_name'     => $filename,
        //         'file_path'     => $filepath,
        //         'full_path'     => realpath($filepath),
        //         'raw_name'      => pathinfo($filename, PATHINFO_FILENAME),
        //         'file_ext'      => '.' . pathinfo($filename, PATHINFO_EXTENSION),
        //         'file_size'     => $file['size'],
        //         'file_type'     => $file['type'],
        //         'is_image'      => $this->isImage($filepath),
        //         'image_width'   => null,
        //         'image_height'  => null,
        //         'uploaded_at'   => date('Y-m-d H:i:s')
        //     ];

        //     // Get image dimensions if it's an image
        //     $index = count($this->uploadedFiles) - 1;
        //     if ($this->uploadedFiles[$index]['is_image']) {
        //         $dimensions = getimagesize($filepath);
        //         if ($dimensions) {
        //             $this->uploadedFiles[$index]['image_width'] = $dimensions[0];
        //             $this->uploadedFiles[$index]['image_height'] = $dimensions[1];
        //         }
        //     }
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {

            $file_data = [
                'field_name'    => $fieldName,
                'original_name' => $file['name'],
                'file_name'     => $filename,
                'file_path'     => $filepath,
                'full_path'     => realpath($filepath),
                'raw_name'      => pathinfo($filename, PATHINFO_FILENAME),
                'file_ext'      => '.' . pathinfo($filename, PATHINFO_EXTENSION),
                'file_size'     => $file['size'],
                'file_type'     => $file['type'],
                'is_image'      => $this->isImage($filepath),
                'image_width'   => null,
                'image_height'  => null,
                'uploaded_at'   => date('Y-m-d H:i:s')
            ];

            // Get image dimensions if it's an image
            if ($file_data['is_image']) {
                $dimensions = getimagesize($filepath);
                if ($dimensions) {
                    $file_data['image_width'] = $dimensions[0];
                    $file_data['image_height'] = $dimensions[1];
                }
            }

            // $index = count($this->uploadedFiles) - 1;

            // if ($this->uploadedFiles[$index]['is_image']) {
            //     $dimensions = getimagesize($filepath);
            //     if ($dimensions) {
            //         $this->uploadedFiles[$index]['image_width'] = $dimensions[0];
            //         $this->uploadedFiles[$index]['image_height'] = $dimensions[1];
            //     }
            // }

            // Create UploadedFile object instead of array
            $this->uploadedFiles[] = new UploadedFile($file_data);
        } else {
            $this->errors[] = "Failed to move uploaded file: {$file['name']}";
        }
    }

    /**
     * Validate uploaded file
     */
    protected function validateFile($file)
    {
        // Check file size
        if ($this->config['max_size'] > 0 && $file['size'] > ($this->config['max_size'] * 1024)) {
            return [
                'valid' => false,
                'error' => "File {$file['name']} exceeds maximum size of {$this->config['max_size']}KB"
            ];
        }

        // Check file type
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types = explode('|', strtolower($this->config['allowed_types']));

        if (!in_array($ext, $allowed_types) && $this->config['allowed_types'] !== '*') {
            return [
                'valid' => false,
                'error' => "File type '{$ext}' is not allowed for {$file['name']}"
            ];
        }

        // Check image dimensions if applicable
        if ($this->isImageByExtension($ext) && ($this->config['max_width'] > 0 || $this->config['max_height'] > 0)) {
            $dimensions = getimagesize($file['tmp_name']);
            if ($dimensions) {
                if ($this->config['max_width'] > 0 && $dimensions[0] > $this->config['max_width']) {
                    return [
                        'valid' => false,
                        'error' => "Image {$file['name']} width ({$dimensions[0]}px) exceeds maximum width ({$this->config['max_width']}px)"
                    ];
                }
                if ($this->config['max_height'] > 0 && $dimensions[1] > $this->config['max_height']) {
                    return [
                        'valid' => false,
                        'error' => "Image {$file['name']} height ({$dimensions[1]}px) exceeds maximum height ({$this->config['max_height']}px)"
                    ];
                }
            }
        }

        return ['valid' => true];
    }

    /**
     * Generate filename based on configuration
     */
    protected function generateFilename($original_name)
    {
        $ext = pathinfo($original_name, PATHINFO_EXTENSION);
        $name = pathinfo($original_name, PATHINFO_FILENAME);

        if ($this->config['encrypt_name']) {
            $name = md5(uniqid() . $original_name);
        } elseif ($this->config['remove_spaces']) {
            $name = preg_replace('/\s+/', '_', $name);
        }

        if ($this->config['file_ext_tolower']) {
            $ext = strtolower($ext);
        }

        $filename = $name . '.' . $ext;

        // Truncate if max_filename is set
        if ($this->config['max_filename'] > 0 && strlen($filename) > $this->config['max_filename']) {
            $name = substr($name, 0, $this->config['max_filename'] - strlen($ext) - 1);
            $filename = $name . '.' . $ext;
        }

        return $filename;
    }

    /**
     * Generate unique filename if file exists
     */
    protected function generateUniqueFilename($filename)
    {
        $path = pathinfo($filename, PATHINFO_DIRNAME);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $counter = 1;
        do {
            $new_filename = $name . '_' . $counter . '.' . $ext;
            $counter++;
        } while (file_exists($this->config['upload_path'] . $new_filename));

        return $new_filename;
    }

    /**
     * Check if file is an image
     */
    protected function isImage($filepath)
    {
        $mime = mime_content_type($filepath);
        return strpos($mime, 'image/') === 0;
    }

    /**
     * Check if file is an image by extension
     */
    protected function isImageByExtension($ext)
    {
        $image_types = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'avif'];
        return in_array(strtolower($ext), $image_types);
    }

    /**
     * Get human-readable upload error message
     */
    protected function getUploadError($error_code, $filename)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => "File {$filename} exceeds upload_max_filesize directive",
            UPLOAD_ERR_FORM_SIZE  => "File {$filename} exceeds MAX_FILE_SIZE directive",
            UPLOAD_ERR_PARTIAL    => "File {$filename} was only partially uploaded",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded for {$filename}",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder for {$filename}",
            UPLOAD_ERR_CANT_WRITE => "Failed to write {$filename} to disk",
            UPLOAD_ERR_EXTENSION  => "File {$filename} upload stopped by extension"
        ];

        return isset($errors[$error_code]) ? $errors[$error_code] : "Unknown upload error for {$filename}";
    }

    /**
     * Reset internal state
     */
    protected function reset()
    {
        $this->uploadedFiles = [];
        $this->errors = [];
    }
}

/**
 * Upload Result Class
 * Provides easy access to upload results and errors
 */
class UploadResult
{

    protected $files = [];
    protected $errors = [];

    public function __construct($files, $errors)
    {
        $this->files = $files;
        $this->errors = $errors;
    }

    /**
     * Check if upload was successful
     */
    public function success()
    {
        return empty($this->errors) && !empty($this->files);
    }

    /**
     * Check if there were any errors
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get all errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get first error
     */
    public function getError()
    {
        return !empty($this->errors) ? $this->errors[0] : null;
    }

    /**
     * Get all uploaded files
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Get first uploaded file
     */
    public function getFile()
    {
        return !empty($this->files) ? $this->files[0] : null;
    }

    /**
     * Get files by field name
     */
    public function getFilesByField($fieldName)
    {
        return array_filter($this->files, function ($file) use ($fieldName) {
            return $file['field_name'] === $fieldName;
        });
    }

    /**
     * Get count of uploaded files
     */
    public function count()
    {
        return count($this->files);
    }

    /**
     * Get upload summary
     */
    public function summary()
    {
        return [
            'total_files' => count($this->files),
            'total_errors' => count($this->errors),
            'success' => $this->success(),
            'files' => $this->files,
            'errors' => $this->errors
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson()
    {
        return json_encode($this->summary(), JSON_PRETTY_PRINT);
    }

    /**
     * Convert to array
     */
    public function getFilesByType($type)
    {
        return array_filter($this->files, function ($file) use ($type) {
            return stripos($file['file_type'], $type) !== false;
        });
    }

    /**
     * Get Images
     * @return array|mixed
     */
    public function getImages()
    {
        return array_filter($this->files, function ($file) {
            return $file['is_image'] === true;
        });
    }

    /**
     * Get total size of uploaded files
     * @return float|int
     */
    public function getTotalSize()
    {
        return array_sum(array_column($this->files, 'file_size'));
    }

    /**
     * Get file names
     * @return array
     */
    public function getFileNames()
    {
        return array_column($this->files, 'file_name');
    }

    /**
     * Get original file names
     * @return array
     */
    public function getOriginalNames()
    {
        return array_column($this->files, 'original_name');
    }

    /**
     * Get all files as arrays (for backwards compatibility)
     */
    public function getFilesAsArray()
    {
        return array_map(function ($file) {
            return $file->toArray();
        }, $this->files);
    }

    /**
     * Get first file as array (for backwards compatibility)
     */
    public function getFileAsArray()
    {
        $file = $this->getFile();
        return $file ? $file->toArray() : null;
    }

    /**
     * Get file details in array format
     * @return array
     */
    public function toArray()
    {
        return [
            'files' => $this->files,
            'errors' => $this->errors,
            'success' => $this->success(),
            'count' => $this->count()
        ];
    }
}

/**
 * UploadedFile Class
 * Represents a single uploaded file with object access
 */
class UploadedFile implements ArrayAccess
{

    public $field_name;
    public $original_name;
    public $file_name;
    public $file_path;
    public $full_path;
    public $raw_name;
    public $file_ext;
    public $file_size;
    public $file_type;
    public $is_image;
    public $image_width;
    public $image_height;
    public $uploaded_at;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Convert to array (for backwards compatibility)
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Get file size in human-readable format
     */
    public function getReadableSize()
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size * 1024; // Convert from KB to bytes

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file URL (requires base_url helper)
     */
    public function getUrl()
    {
        $path = str_replace('./', '', $this->file_path);
        return base_url($path);
    }

    /**
     * Check if file exists
     */
    public function exists()
    {
        return file_exists($this->full_path);
    }

    /**
     * Delete the file
     */
    public function delete()
    {
        if ($this->exists()) {
            return unlink($this->full_path);
        }
        return false;
    }

    // ArrayAccess implementation for backwards compatibility
    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = null;
        }
    }
}
