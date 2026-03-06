<?php

if (!function_exists('setting')) {
    /**
     * Get system setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key, $default = null)
    {
        return \App\Models\SystemSetting::get($key, $default);
    }
}

if (!function_exists('app_name')) {
    /**
     * Get application name
     *
     * @return string
     */
    function app_name()
    {
        return setting('app_name', config('app.name', 'CBT SMK'));
    }
}

if (!function_exists('school_name')) {
    /**
     * Get school name
     *
     * @return string
     */
    function school_name()
    {
        return setting('school_name', 'SMK Negeri 1');
    }
}

if (!function_exists('school_logo')) {
    /**
     * Get school logo URL
     *
     * @return string|null
     */
    function school_logo()
    {
        $logo = setting('logo');
        return $logo ? asset('storage/' . $logo) : null;
    }
}

if (!function_exists('school_logo_small')) {
    /**
     * Get small school logo URL
     *
     * @return string|null
     */
    function school_logo_small()
    {
        $logo = setting('logo_small');
        return $logo ? asset('storage/' . $logo) : null;
    }
}

if (!function_exists('primary_color')) {
    /**
     * Get primary color
     *
     * @return string
     */
    function primary_color()
    {
        return setting('primary_color', '#4f46e5');
    }
}

if (!function_exists('secondary_color')) {
    /**
     * Get secondary color
     *
     * @return string
     */
    function secondary_color()
    {
        return setting('secondary_color', '#7c3aed');
    }
}
