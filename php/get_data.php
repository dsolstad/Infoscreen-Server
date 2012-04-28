<?php

header('Content-type: application/json');
include("./systemstats.class.php");

$stats = array();

if ($_GET['q'] == 'all') {
    $class_methods = get_class_methods('SystemStats');
    foreach ($class_methods as $method_name) {
        $stats[$method_name] = SystemStats::$method_name();
    }
} else if ($_GET['q'] == 'cpu') {
    $stats['cpu_load_perc_free'] = SystemStats::cpu_load_perc_free();
    $stats['cpu_load_perc_used'] = SystemStats::cpu_load_perc_used();
} else if ($_GET['q'] == 'network') {
    $stats['dl_speed'] = SystemStats::dl_speed();
    $stats['ul_speed'] = SystemStats::ul_speed();
    $stats['total_downloaded'] = SystemStats::total_downloaded();
    $stats['total_uploaded'] = SystemStats::total_uploaded();
}

print json_encode($stats);

?>