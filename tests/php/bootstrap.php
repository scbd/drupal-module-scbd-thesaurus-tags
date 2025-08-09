<?php

// Minimal bootstrap for unit tests that don't need full Drupal.
// Provide stubs for global functions used in .install file if loaded.
if (!function_exists('t')) {
    function t($string, array $args = [])
    {
        return strtr($string, $args);
    }
}
