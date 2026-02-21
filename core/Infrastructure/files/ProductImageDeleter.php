<?php

namespace Infrastructure\Files;

class ProductImageDeleter {

     public function delete(string $path): void
     {
        $normalized = ltrim($path, '/\\');

        // If DB already stores a path that starts with "storage/", avoid duplicating it
        if (stripos($normalized, 'storage/') === 0 || stripos($normalized, 'storage\\') === 0) {
            $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . $normalized;
        } else {
            $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $normalized;
        }

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
     }
}
