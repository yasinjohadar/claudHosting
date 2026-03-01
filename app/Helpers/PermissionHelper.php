<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * التحقق من وجود صلاحية معينة
     */
    public static function hasPermission($permission)
    {
        return Auth::user()->hasPermissionTo($permission);
    }

    /**
     * التحقق من وجود أي صلاحية من مجموعة صلاحيات
     */
    public static function hasAnyPermission($permissions)
    {
        return Auth::user()->hasAnyPermission($permissions);
    }

    /**
     * التحقق من وجود جميع الصلاحيات
     */
    public static function hasAllPermissions($permissions)
    {
        return Auth::user()->hasAllPermissions($permissions);
    }

    /**
     * التحقق من وجود دور معين
     */
    public static function hasRole($role)
    {
        return Auth::user()->hasRole($role);
    }

    /**
     * التحقق من وجود أي دور من مجموعة أدوار
     */
    public static function hasAnyRole($roles)
    {
        return Auth::user()->hasAnyRole($roles);
    }

    /**
     * الحصول على جميع صلاحيات المستخدم
     */
    public static function getUserPermissions()
    {
        return Auth::user()->getAllPermissions();
    }

    /**
     * الحصول على جميع أدوار المستخدم
     */
    public static function getUserRoles()
    {
        return Auth::user()->getRoleNames();
    }
} 