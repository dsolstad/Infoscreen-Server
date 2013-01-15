<?php

##
##    Author: Daniel Solstad (http://dsolstad.com)
##    Licence: GPL
##

class SystemStats {

    private static $network_dev = 'eth0';
    
    public static function kernel() {
        return trim(shell_exec('uname -r'));
    }

    public static function hostname() {
        return trim(shell_exec("hostname"));
    }

    public static function distro() {
        $info = shell_exec('cat /etc/*-release');
        preg_match('/DISTRIB_DESCRIPTION="(.*?)"/', $info, $m);
        if ($m[1]) {
            return trim($m[1]);
        } else {
            return "N/A";
        }
    }
    
    public static function top_ram() {
        $info = shell_exec("ps aux | sort -b -r -g -k 4 | head -3 | awk '{print $4\"%\",$11}'");
        $new_info = '';
        foreach (explode("\n", $info) as $line) {
            $words = explode(" ", $line);
            $new_info .= array_shift($words);
            if (strpos($words[0], '/') !== false) {
                $folders = explode("/", $words[0]);
                $new_info .= ' ' . array_pop($folders) . "\n";
            } else {
                $new_info .= ' ' . $words[0] . "\n";
            }
        }
        return trim($new_info);
    }
    
    public static function top_cpu() {
        $info = shell_exec("ps aux | sort -b -r -g -k 3 | head -3 | awk '{print $3\"%\",$11}'");
        $new_info = '';
        foreach (explode("\n", $info) as $line) {
            $words = explode(" ", $line);
            $new_info .= array_shift($words);
            if (strpos($words[0], '/') !== false) {
                $folders = explode("/", $words[0]);
                $new_info .= ' ' . array_pop($folders) . "\n";
            } else {
                $new_info .= ' ' . $words[0] . "\n";
            }
        }
        return trim($new_info);
    }

    /*
    public static function top_both() {
        return shell_exec("ps aux | sort -b -r -k 3 -k 4 | head -6 | tail -5 | awk '{print $3,$4,$11}'");
    }
    */
    public static function uptime() {
        $info = shell_exec('uptime');
        preg_match('/(\d{1,2}:\d{1,2}), /', $info, $m);
        return trim($m[1]);
    }

    public static function all_processes() {
        return trim(shell_exec('ps auxh | wc -l'));
    }
 
    public static function running_processes() {
        return trim(shell_exec('ps -e | grep -v ? | tail -n +2 | wc -l'));
    }

    public static function cpu_freq() {
        $info = shell_exec("cat /proc/cpuinfo | grep MHz | awk '{print $4}' | head -1");
        // Returns frequency in gigahertz
        return round($info / 1000, 2) . " GHz";
    }

    public static function cpu_cores() {
        $info = shell_exec('cat /proc/cpuinfo');
        return count(explode("\n\n", $info)) - 1;
    }

    public static function cpu_model() {
        $info = shell_exec("cat /proc/cpuinfo | grep 'model name' | head -1");
        preg_match('/: (.*?)\s{2,}/', $info, $m);
        return trim(str_replace('CPU', '', $m[1])); 
    }

    public static function cpu_temp() {
        // Get temp from ´sensors´
        $info = shell_exec('sensors');
        preg_match('/:\s+([+|-].*?)\s*?\(/', $info, $m);
        $info = $m[1];
        if (!$info) {
            // Get temp from thermal
            $info = shell_exec('cat /sys/class/thermal/thermal_zone0/temp');
            $info = $info / 1000;
        }
        if (!$info) {
            return "N/A";
        } else {
            return trim($info);
        }
    }
    
    
    // Alternative: top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%\id.*/\1/" | awk '{print 100 - $1"%"}'
    
    public static function cpu_load_perc_free() {
        return (int) trim(shell_exec("vmstat | tail -1 | awk '{print $15}'"));
    }
    
    public static function cpu_load_perc_used() {
        return 100 - self::cpu_load_perc_free();
    }

    public static function ram_total() {
        $info = shell_exec("cat /proc/meminfo | grep MemTotal: | awk '{print $2}'");
        // Returns size in gigabytes
        return round($info / pow(1024,2), 2) . " GiB";
    }

    public static function ram_free() {
        $info = shell_exec("cat /proc/meminfo | grep MemFree: | awk '{print $2}'");
        // Returns size in gigabytes
        return round($info / pow(1024,2), 2) . " GiB";
    }

    public static function ram_used() {
        // Returns size in megabytes
        // Need to remove the prefix before minus
        return substr(self::ram_total(), 0, -4) - substr(self::ram_free(), 0, -4) . " GiB";
    }
 
    public static function gfx_model() {
        $info = shell_exec('lspci | grep -i vga');
        preg_match('/: (.*?) \(/', $info, $m);
        return trim($m[1]);
    }

    public static function wlan_essid() {
        $info = shell_exec('iwconfig');
        if (preg_match('/ESSID:"(.*?)"\n/', $info, $m)) {
            return trim($m[1]);
        } else {
            return 'N/A';
        }
    }
    
    public static function network_device() {
        return self::$network_dev;
    }
    
    public static function mac_addr() {
        $dev = escapeshellarg(self::$network_dev);
        return shell_exec("/sbin/ifconfig ".$dev." | grep -o -E '([[:xdigit:]]{1,2}:){5}[[:xdigit:]]{1,2}'");
    }
    
    public static function ipv4_addr() {
        $dev = escapeshellarg(self::$network_dev);
        return trim(shell_exec('/sbin/ifconfig '.$dev.' | sed \'/inet\ /!d;s/.*r://g;s/\ .*//g\''));
    }
    
    public static function ipv6_addr() {
        $dev = escapeshellarg(self::$network_dev);
        return trim(shell_exec('ifconfig '.$dev.' | grep inet6 | awk \'{print $3}\' | head -1'));
    }
    
    public static function open_ports_known() {
        $info = trim(shell_exec("/bin/netstat -t -l | grep tcp | grep -v -i localhost | awk '{print $4}'"));
        $info = str_replace("\r", "", $info);
        $output = array();
        foreach (explode("\n", $info) as $line) {
            $pop = trim(array_pop(explode(':', $line)));
            if (!is_numeric($pop)) {
                $output[] = $pop;
            }
        }
        if (!empty($output)) {
            return join(", ", array_unique($output));
        } else {
            return 'N/A';
        }
    }

    public static function open_ports_unknown() {
        $info = trim(shell_exec("/bin/netstat -t -l | grep tcp | grep -v -i localhost | awk '{print $4}'"));
        $info = str_replace("\r", "", $info);
        $output = array();
        foreach (explode("\n", $info) as $line) {
            $pop = trim(array_pop(explode(':', $line)));
            if (is_numeric($pop)) {
                $output[] = $pop;
            }
        }
        if (!empty($output)) {
            return join(", ", array_unique($output));
        } else {
            return 'N/A';
        }
    }

    public static function dl_speed() {
        $dev = escapeshellarg(self::$network_dev);
        $first = shell_exec("cat /proc/net/dev | grep ".$dev." | awk '{print $2}'");
        sleep(1);
        $second = shell_exec("cat /proc/net/dev | grep ".$dev." | awk '{print $2}'");
        $speed = $second - $first;
        if (($speed / 1024) > 1024) {
            return round($speed / pow(1024, 2)) . " MiB/s";
        } else {
            return round($speed / 1024) . " KiB/s";
        }
    }
    
     public static function ul_speed() {
        $dev = escapeshellarg(self::$network_dev);
        $first = shell_exec("cat /proc/net/dev | grep ".$dev." | awk '{print $10}'");
        sleep(1);
        $second = shell_exec("cat /proc/net/dev | grep ".$dev." | awk '{print $10}'");
        $speed = $second - $first;
        if (($speed / 1024) > 1024) {
            return round($speed / pow(1024, 2)) . " MiB/s";
        } else {
            return round($speed / 1024) . " KiB/s";
        }
    }
    
    public static function total_downloaded() {
        $dev = escapeshellarg(self::$network_dev);
        $info = shell_exec("cat /proc/net/dev | grep ".$dev." | awk '{print $2}'");
        if (($info / pow(1024, 2)) > pow(1024, 2)) {
            return round($info / pow(1024, 3)) . " GiB";
        } else {
            return round($info / pow(1024, 2)) . " MiB";
        }
    }
    
    public static function total_uploaded() {
        $dev = escapeshellarg(self::$network_dev);
        $info = shell_exec("cat /proc/net/dev | grep ".$dev." | awk '{print $10}'");
        if (($info / pow(1024, 2)) > pow(1024, 2)) {
            return round($info / pow(1024, 3)) . " GiB";
        } else {
            return round($info / pow(1024, 2)) . " MiB";
        }
    }
    
    public static function fs_root_total() {
        // Returns size in gigabytes
        return trim(shell_exec('df -h | tail -n +2 | head -1 | awk \'{print $2}\' | sed \'$s/.$//\'')) . " GiB";
    }
    
    public static function fs_root_used() {
        // Returns size in gigabytes
        return trim(shell_exec('df -h | tail -n +2 | head -1 | awk \'{print $3}\' | sed \'$s/.$//\'')) . " GiB";
    }
    
    public static function fs_root_free() {
        // Returns size in gigabytes
        return trim(shell_exec('df -h | tail -n +2 | head -1 | awk \'{print $4}\' | sed \'$s/.$//\'')) . " GiB";
    }
    
    public static function fs_root_perc_used() {
        return trim(shell_exec("df -h | tail -n +2 | head -1 | awk '{print $5}'"));
    }
    
    public static function fs_root_type() {
        return trim(shell_exec("cat /etc/fstab | grep '/[^a-z]' | awk '{print $3}'"));
    }
    
    public static function fs_swap_total() {
        $info = shell_exec("/sbin/swapon -s | grep /dev | awk '{print $3}'");
        return round($info / 1024) . " MiB"; // Returns size in megabytes
    }
    
    public static function fs_swap_used() {
        $info = shell_exec("/sbin/swapon -s | grep /dev | awk '{print $4}'");
        return round($info / 1024) . " MiB"; // Returns size in megabytes
    }
    
    public static function fs_swap_free() {
        // Returns size in megabytes
        return round(substr(self::fs_swap_total(), 0, -4) - substr(self::fs_swap_used(), 0, -4)) . " MiB";
    }
    
    public static function fs_swap_perc_used() {
        // Need to remove prefix before divsion
        return substr(self::fs_swap_free(), 0, -4) / substr(self::fs_swap_total(), 0, -4) * 100;
    }
    
    public static function fs_swap_swappiness() {
        return trim(shell_exec('cat /proc/sys/vm/swappiness'));
    }

}

?>
