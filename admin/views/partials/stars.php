<?php
/**
 * Renders N filled stars + (5-N) hollow stars.
 * Safe to include multiple times on the same page.
 */
if (!function_exists('render_stars')) {
    function render_stars(float $rating, int $size = 16): string
    {
        $html = '<span class="inline-flex items-center gap-0.5 align-middle">';
        for ($i = 1; $i <= 5; $i++) {
            $filled = $i <= round($rating);
            $stroke = $filled ? '#f59e0b' : '#cbd5e1';
            $fill   = $filled ? '#f59e0b' : 'none';
            $html .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" '
                   . 'fill="' . $fill . '" stroke="' . $stroke . '" stroke-width="1.5" '
                   . 'stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;">'
                   . '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'
                   . '</svg>';
        }
        return $html . '</span>';
    }
}