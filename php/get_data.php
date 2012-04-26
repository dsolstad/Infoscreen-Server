<?php

header('Content-type: application/json');
include("./systemstats.class.php");

$stats = array();
$q = $_GET['q'];

if ($q == "all") {
    $class_methods = get_class_methods('SystemStats');
    foreach ($class_methods as $method_name) {
        $stats[$method_name] = SystemStats::$method_name();
    }

} else if ($q == "cpu") {
    $stats['cpu_load_perc_free'] = SystemStats::cpu_load_perc_free();
    $stats['cpu_load_perc_used'] = SystemStats::cpu_load_perc_used();
} else if ($q == "network") {
    $stats['dl_speed'] = SystemStats::dl_speed();
    $stats['ul_speed'] = SystemStats::ul_speed();
}

print json_encode($stats);

?>