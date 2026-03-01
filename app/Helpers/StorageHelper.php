<?php

if (!function_exists('storage_url')) {
    /**
     * Get the URL for a file stored in storage/app/public
     * Works correctly with/without /public in URL
     * 
     * @param string $path The file path relative to storage/app/public
     * @return string The full URL to the file
     */
    function storage_url($path)
    {
        // Remove 'storage/' prefix if exists (already handled by symbolic link)
        $cleanPath = ltrim($path, '/');
        $cleanPath = str_replace('storage/', '', $cleanPath);
        
        // Use asset() which works correctly with symbolic links
        return asset('storage/' . $cleanPath);
    }
}

if (!function_exists('blog_image_url')) {
    /**
     * Get the URL for a blog post featured image
     * Tries multiple methods to ensure the image is accessible
     * 
     * @param string|null $imagePath The image path from database
     * @return string The full URL to the image
     */
    function blog_image_url($imagePath)
    {
        if (empty($imagePath)) {
            return asset('frontend/assets/images/placeholder.jpg');
        }

        // Clean the path
        $imagePath = ltrim($imagePath, '/');
        $filename = basename($imagePath);
        
        // Method 1: Try StorageHelperService (dynamic storage) - FIRST
        try {
            $storageHelper = app(\App\Services\Storage\StorageHelperService::class);
            $url = $storageHelper->getFileUrl('public', $imagePath);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        } catch (\Exception $e) {
            // Continue to next method
        }
        
        // Method 2: Try route (local storage fallback) - SECOND
        try {
            if (strpos($imagePath, 'blog/images/') !== false) {
                return route('blog.image', ['filename' => $filename]);
            }
        } catch (\Exception $e) {
            // Continue to next method
        }
        
        // Method 3: Fallback to asset (requires storage link) - LAST
        return asset('storage/' . $imagePath);
    }
}

if (!function_exists('course_image_url')) {
    /**
     * Get the URL for a course image
     * Tries multiple methods to ensure the image is accessible
     * 
     * @param string|null $imagePath The image path from database
     * @return string The full URL to the image
     */
    function course_image_url($imagePath)
    {
        if (empty($imagePath)) {
            return asset('frontend/assets/img/default-course.jpg');
        }

        // Clean the path
        $imagePath = ltrim($imagePath, '/');
        $filename = basename($imagePath);
        
        // Method 1: Try StorageHelperService (dynamic storage) - FIRST
        try {
            $storageHelper = app(\App\Services\Storage\StorageHelperService::class);
            $url = $storageHelper->getFileUrl('public', $imagePath);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        } catch (\Exception $e) {
            // Continue to next method
        }
        
        // Method 2: Try route (local storage fallback) - SECOND
        try {
            if (strpos($imagePath, 'courses/images/') !== false) {
                return route('course.image', ['filename' => $filename]);
            }
            if (strpos($imagePath, 'courses/covers/') !== false) {
                return route('course.cover', ['filename' => $filename]);
            }
            if (strpos($imagePath, 'courses/thumbnails/') !== false) {
                return route('course.thumbnail', ['filename' => $filename]);
            }
        } catch (\Exception $e) {
            // Continue to next method
        }
        
        // Method 3: Fallback to asset (requires storage link) - LAST
        return asset('storage/' . $imagePath);
    }
}

if (!function_exists('course_cover_url')) {
    /**
     * Get the URL for a course cover image
     *
     * @param string|null $imagePath The cover path from database (e.g. courses/covers/xxx.jpg)
     * @return string The full URL to the image
     */
    function course_cover_url($imagePath)
    {
        if (empty($imagePath)) {
            return asset('frontend/assets/img/default-course.jpg');
        }
        $imagePath = ltrim($imagePath, '/');
        $filename = basename($imagePath);
        try {
            $storageHelper = app(\App\Services\Storage\StorageHelperService::class);
            $url = $storageHelper->getFileUrl('public', $imagePath);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        } catch (\Exception $e) {
            // continue
        }
        try {
            if (strpos($imagePath, 'courses/covers/') !== false) {
                return route('course.cover', ['filename' => $filename]);
            }
        } catch (\Exception $e) {
            // continue
        }
        return asset('storage/' . $imagePath);
    }
}

if (!function_exists('storage_disk_url')) {
    /**
     * Get the URL for a file stored in a specific disk (dynamic storage)
     * 
     * @param string $disk The disk name (e.g., 'public', 'images')
     * @param string $path The file path
     * @return string The full URL to the file
     */
    function storage_disk_url(string $disk, string $path): string
    {
        try {
            $storageHelper = app(\App\Services\Storage\StorageHelperService::class);
            $url = $storageHelper->getFileUrl($disk, $path);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                return $url;
            }
        } catch (\Exception $e) {
            // Fallback to default storage URL
        }
        
        // Fallback to asset if dynamic storage fails
        return asset('storage/' . ltrim($path, '/'));
    }
}
