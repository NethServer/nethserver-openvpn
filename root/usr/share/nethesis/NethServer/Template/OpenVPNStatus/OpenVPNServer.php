<?php

if ($view['server']['status'] == 'enabled') {
    echo "<div class='vpn-item'>";
    echo "<h2>".$T('ovpn_server')."</h2>";
    echo "<dl>";
    echo "<dt>".$T('ovpn_mode')."</dt><dd>"; echo $T($view['server']['mode']); echo "</dd>";
    echo "<dt>".$T('ovpn_auth')."</dt><dd>"; echo $T($view['server']['auth']); echo "</dd>";
    echo "<dt>".$T('ovpn_range')."</dt><dd>{$view['server']['range']}</dd>";
    echo "<dt>".$T('ovpn_port')."</dt><dd>{$view['server']['port']}</dd>";

    echo "</dl>";
    echo "</div>";

} else {
    echo "<div class='vpn-item'>";
    echo "<h2>".$T('vpn_disabled')."</h2>";
    echo "</div>";

}
