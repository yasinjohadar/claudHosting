<?php

namespace App\Contracts;

interface BackupStorageInterface
{
    /**
     * تخزين ملف
     */
    public function store(string $path, string $content): bool;

    /**
     * استرجاع ملف
     */
    public function retrieve(string $path): string;

    /**
     * حذف ملف
     */
    public function delete(string $path): bool;

    /**
     * التحقق من وجود ملف
     */
    public function exists(string $path): bool;

    /**
     * قائمة الملفات
     */
    public function list(string $prefix = ''): array;

    /**
     * الحصول على حجم الملف
     */
    public function getSize(string $path): int;

    /**
     * اختبار الاتصال
     */
    public function testConnection(): bool;

    /**
     * الحصول على المساحة المتاحة
     */
    public function getAvailableSpace(): ?int;

    /**
     * الحصول على metadata الملف
     */
    public function getMetadata(string $path): array;
}

