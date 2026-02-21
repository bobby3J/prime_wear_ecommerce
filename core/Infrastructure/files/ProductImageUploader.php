<?php

namespace Infrastructure\Files;

class ProductImageUploader 
{
    private string $uploadDir;

    public function __construct()
    {
        // Public storage path (NOT core)
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/uploads/products/';
    }

    public function upload(array $file): string 
    {
        $filename = uniqid('product_') . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);

        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0777, true)) {
                throw new \RuntimeException('Failed to create upload directory: ' . $this->uploadDir);
            }
        }

        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('No valid uploaded file.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload error code: ' . $file['error']);
        }

        $destination = $this->uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        // Store RELATIVE public path in DB
        return "uploads/products/$filename";
    }
}
