<?php
$singbox_bin = '/usr/bin/sing-box';
$log = '/etc/neko/tmp/log.txt';

function _writeToLog($message) {
    global $log;
    $time = (new DateTime())->format('H:i:s');
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
    $fw = _detectFW();
    $pid = _getSingboxPID();
    if (!$pid) _writeToLog("Sing-box chưa chạy, cần start thủ công.");
    else _writeToLog("Sing-box đang chạy PID: $pid");

    if ($fw === 'iptables') {
        shell_exec("ip rule del fwmark 1 table 100 2>/dev/null");
        shell_exec("ip route del local default dev lo table 100 2>/dev/null");
        shell_exec("ip rule add fwmark 1 table 100");
        shell_exec("ip route add local default dev lo table 100");

        $chains = ['PREROUTING'];
        foreach ($chains as $chain) {
            $subnets = ['0.0.0.0/8','10.0.0.0/8','127.0.0.0/8','172.16.0.0/12','192.168.0.0/16','224.0.0.0/4'];
            foreach ($subnets as $net) {
                shell_exec("iptables -t mangle -D $chain -d $net -j RETURN 2>/dev/null");
                shell_exec("iptables -t mangle -A $chain -d $net -j RETURN");
            }
            shell_exec("iptables -t mangle -D $chain -p tcp -j TPROXY --on-port 9888 --tproxy-mark 1 2>/dev/null");
            shell_exec("iptables -t mangle -D $chain -p udp -j TPROXY --on-port 9888 --tproxy-mark 1 2>/dev/null");
            shell_exec("iptables -t mangle -A $chain -p tcp -j TPROXY --on-port 9888 --tproxy-mark 1");
            shell_exec("iptables -t mangle -A $chain -p udp -j TPROXY --on-port 9888 --tproxy-mark 1");
            shell_exec("iptables -t nat -D $chain -p udp --dport 53 -j REDIRECT --to-ports 1053 2>/dev/null");
            shell_exec("iptables -t nat -D $chain -p tcp --dport 53 -j REDIRECT --to-ports 1053 2>/dev/null");
            shell_exec("iptables -t nat -A $chain -p udp --dport 53 -j REDIRECT --to-ports 1053");
            shell_exec("iptables -t nat -A $chain -p tcp --dport 53 -j REDIRECT --to-ports 1053");
        }
    } else {
        shell_exec("nft flush table ip mangle 2>/dev/null");
        shell_exec("nft flush table ip6 mangle 2>/dev/null");
        shell_exec("nft add table ip mangle 2>/dev/null");
        shell_exec("nft add table ip6 mangle 2>/dev/null");
        $rules = [
            "nft 'add chain ip mangle prerouting { type filter hook prerouting priority mangle; }'",
            "nft 'add chain ip mangle output { type filter hook output priority mangle; }'",
            "nft 'add chain ip6 mangle prerouting { type filter hook prerouting priority mangle; }'",
            "nft 'add chain ip6 mangle output { type filter hook output priority mangle; }'",
            "nft add rule ip mangle prerouting ip daddr 0.0.0.0/8 return",
            "nft add rule ip mangle prerouting ip daddr 10.0.0.0/8 return",
            "nft add rule ip mangle prerouting ip daddr 127.0.0.0/8 return",
            "nft add rule ip mangle prerouting ip daddr 172.16.0.0/12 return",
            "nft add rule ip mangle prerouting ip daddr 192.168.0.0/16 return",
            "nft add rule ip mangle prerouting ip daddr 224.0.0.0/4 return",
            "nft add rule ip mangle prerouting udp dport 53 redirect to :1053",
            "nft add rule ip mangle prerouting tcp dport 53 redirect to :1053",
            "nft add rule ip mangle prerouting tcp meta mark set 1 tproxy to :9888",
            "nft add rule ip mangle prerouting udp meta mark set 1 tproxy to :9888"
        ];
        foreach ($rules as $r) shell_exec($r);
    }

    //shell_exec("/etc/init.d/firewall restart");
    _writeToLog("Đã thêm rules và restart Firewall ($fw).");
}

function disable_fw() {
    $fw = _detectFW();
    if ($fw === 'iptables') {
        shell_exec("ip rule del fwmark 1 table 100 2>/dev/null");
        shell_exec("ip route del local default dev lo table 100 2>/dev/null");
        $chains = ['PREROUTING'];
        foreach ($chains as $chain) {
            $subnets = ['0.0.0.0/8','10.0.0.0/8','127.0.0.0/8','172.16.0.0/12','192.168.0.0/16','224.0.0.0/4'];
            foreach ($subnets as $net) shell_exec("iptables -t mangle -D $chain -d $net -j RETURN 2>/dev/null");
            shell_exec("iptables -t mangle -D $chain -p tcp -j TPROXY --on-port 9888 --tproxy-mark 1 2>/dev/null");
            shell_exec("iptables -t mangle -D $chain -p udp -j TPROXY --on-port 9888 --tproxy-mark 1 2>/dev/null");
            shell_exec("iptables -t nat -D $chain -p udp --dport 53 -j REDIRECT --to-ports 1053 2>/dev/null");
            shell_exec("iptables -t nat -D $chain -p tcp --dport 53 -j REDIRECT --to-ports 1053 2>/dev/null");
        }
    } else {
        shell_exec("nft flush table ip mangle 2>/dev/null");
        shell_exec("nft flush table ip6 mangle 2>/dev/null");
    }

	//shell_exec("/etc/init.d/firewall restart");
    _writeToLog("Đã xóa tất cả rules Sing-box ($fw)");
}