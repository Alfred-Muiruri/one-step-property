<?php
/** Helpers for the dashboard bar charts (data is read live from the database on every page load). */

/** Last $n calendar months as ['2026-05' => 'May 26', ...] (oldest first). */
function last_months($n = 6) {
    $out = [];
    for ($i = $n - 1; $i >= 0; $i--) {
        $t = strtotime("first day of -$i month");
        $out[date('Y-m', $t)] = date('M y', $t);
    }
    return $out;
}

/** Print the Chart.js loader once, then a bar chart bound to <canvas id=$id>. */
function render_bar_chart($id, array $labels, array $datasets, $stacked = false, $horizontal = false) {
    static $loaded = false;
    if (!$loaded) { echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>'; $loaded = true; }
    $cfg = ['labels' => array_values($labels), 'datasets' => $datasets, 'stacked' => $stacked, 'horizontal' => $horizontal];
    echo '<script>document.addEventListener("DOMContentLoaded",function(){osBarChart(' . json_encode($id) . ',' . json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ');});</script>';
}
