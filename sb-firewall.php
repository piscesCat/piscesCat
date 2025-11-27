<?php
$singbox_bin = '/usr/bin/sing-box';
$log = '/etc/neko/tmp/log.txt';

$subnets = ['0.0.0.0/8','10.0.0.0/8','127.0.0.0/8','172.16.0.0/12','192.168.0.0/16','224.0.0.0/4'];
$ports = [22,67,68,69,123,161,445,3389];

function _writeToLog($message) {
    global $log;
    $time = (new DateTime())->format('Y-m-d H:i:s');
    file_put_contents($log, "[ $time ] $message\n", FILE_APPEND);
}

function _getSingboxPID() {
    global $singbox_bin;
    exec("pgrep -f " . escapeshellarg($singbox_bin), $output);
    return isset($output[0]) ? $output[0] : null;
}

function _detectFW() {
    if (shell_exec("command -v nft")) return 'nft';
    return 'iptables';
}

function start_fw() {
    global $subnets, $ports;
    $fw = _detectFW();
    $pid = _getSingboxPID();

    if (!$pid) _writeToLog("Sing-box chưa chạy, firewall rules không được áp dụng.");
    else _writeToLog("Phát hiện Sing-box đang chạy, PID=$pid.");

    if ($fw === 'iptables') {
        shell_exec("ip rule del fwmark 1 table 100 2>/dev/null");
        shell_exec("ip route del local default dev lo table 100 2>/dev/null");
        shell_exec("ip rule add fwmark 1 table 100");
        shell_exec("ip route add local default dev lo table 100");

        foreach ($subnets as $net) {
            shell_exec("iptables -t mangle -D PREROUTING -d $net -j RETURN 2>/dev/null");
            shell_exec("iptables -t mangle -A PREROUTING -d $net -j RETURN");
        }

        foreach ($ports as $p) {
            shell_exec("iptables -t mangle -D PREROUTING -p tcp --dport $p -j RETURN 2>/dev/null");
            shell_exec("iptables -t mangle -D PREROUTING -p udp --dport $p -j RETURN 2>/dev/null");
            shell_exec("iptables -t mangle -A PREROUTING -p tcp --dport $p -j RETURN");
            shell_exec("iptables -t mangle -A PREROUTING -p udp --dport $p -j RETURN");
        }

        shell_exec("iptables -t mangle -D PREROUTING -p tcp -j TPROXY --on-port 9888 --tproxy-mark 1 2>/dev/null");
        shell_exec("iptables -t mangle -D PREROUTING -p udp -j TPROXY --on-port 9888 --tproxy-mark 1 2>/dev/null");
        shell_exec("iptables -t mangle -A PREROUTING -p tcp -j TPROXY --on-port 9888 --tproxy-mark 1");
        shell_exec("iptables -t mangle -A PREROUTING -p udp -j TPROXY --on-port 9888 --tproxy-mark 1");

        shell_exec("iptables -t nat -D PREROUTING -p udp --dport 53 -j REDIRECT --to-ports 1053 2>/dev/null");
        shell_exec("iptables -t nat -D PREROUTING -p tcp --dport 53 -j REDIRECT --to-ports 1053 2>/dev/null");
        shell_exec("iptables -t nat -A PREROUTING -p udp --dport 53 -j REDIRECT --to-ports 1053");
        shell_exec("iptables -t nat -A PREROUTING -p tcp --dport 53 -j REDIRECT --to-ports 1053");

        _writeToLog("Đã áp dụng firewall rules bằng iptables.");
    } else {
        shell_exec("nft flush table ip mangle 2>/dev/null");
        shell_exec("nft flush table ip6 mangle 2>/dev/null");
        shell_exec("nft add table ip mangle 2>/dev/null");
        shell_exec("nft add table ip6 mangle 2>/dev/null");

        shell_exec("nft 'add chain ip mangle prerouting { type filter hook prerouting priority mangle; }'");
        shell_exec("nft 'add chain ip mangle output { type filter hook output priority mangle; }'");
        shell_exec("nft 'add chain ip6 mangle prerouting { type filter hook prerouting priority mangle; }'");
        shell_exec("nft 'add chain ip6 mangle output { type filter hook output priority mangle; }'");

        foreach ($subnets as $net) {
            shell_exec("nft add rule ip mangle prerouting ip daddr $net return");
        }

        foreach ($ports as $p) {
            shell_exec("nft add rule ip mangle prerouting tcp dport $p return");
            shell_exec("nft add rule ip mangle prerouting udp dport $p return");
        }

        shell_exec("nft add rule ip mangle prerouting udp dport 53 redirect to :1053");
        shell_exec("nft add rule ip mangle prerouting tcp dport 53 redirect to :1053");
        shell_exec("nft add rule ip mangle prerouting tcp meta mark set 1 tproxy to :9888");
        shell_exec("nft add rule ip mangle prerouting udp meta mark set 1 tproxy to :9888");

        _writeToLog("Đã áp dụng firewall rules bằng nftables.");
    }
}

function disable_fw() {
    global $subnets, $ports;
    $fw = _detectFW();

    if ($fw === 'iptables') {
        shell_exec("ip rule del fwmark 1 table 100 2>/dev/null");
        shell_exec("ip route del local default dev lo table 100 2>/dev/null");

        foreach ($subnets as $net)
            shell_exec("iptables -t mangle -D PREROUTING -d $net -j RETURN 2>/dev/null");

        foreach ($ports as $p) {
            shell_exec("iptables -t mangle -D PREROUTING -p tcp --dport $p -j RETURN 2>/dev/null");
            shell_exec("iptables -t mangle -D PREROUTING -p udp --dport $p -j RETURN 2>/dev/null");
        }

        shell_exec("iptables -t mangle -D PREROUTING -p tcp -j TPROXY --on-port 9888 --tproxy-mark 1 2>/dev/null");
        shell_exec("iptables -t mangle -D PREROUTING -p udp -j TPROXY --on-port 9888 --tproxy-mark 1 2>/dev/null");
        shell_exec("iptables -t nat -D PREROUTING -p udp --dport 53 -j REDIRECT --to-ports 1053 2>/dev/null");
        shell_exec("iptables -t nat -D PREROUTING -p tcp --dport 53 -j REDIRECT --to-ports 1053 2>/dev/null");

        _writeToLog("Đã gỡ toàn bộ firewall rules (iptables).");
    } else {
        shell_exec("nft flush table ip mangle 2>/dev/null");
        shell_exec("nft flush table ip6 mangle 2>/dev/null");

        _writeToLog("Đã gỡ toàn bộ firewall rules (nftables).");
    }
}