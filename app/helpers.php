<?php

if (!function_exists('formatPeso')) {
    function formatPeso($value)
    {
        if (!is_numeric($value)) {
            return '₱0.00';
        }

        if ($value >= 1_000_000_000) {
            return '₱' . number_format($value / 1_000_000_000, 2) . 'B';
        } elseif ($value >= 1_000_000) {
            return '₱' . number_format($value / 1_000_000, 2) . 'M';
        } elseif ($value >= 1_000) {
            return '₱' . number_format($value / 1_000, 2) . 'K';
        }

        return '₱' . number_format($value, 2);
    }
}
