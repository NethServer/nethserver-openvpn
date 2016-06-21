<?php

$view->requireFlag($view::INSET_DIALOG);

echo $view->header()->setAttribute('template', $T('download-vpn_Header'));

$types = array('ovpn','pem','pkcs12','ca');
echo "<ul>";
foreach ($types as $type) {
    echo "<li style='margin-top: 5px;'>";
    echo $view->textLabel($type)->setAttribute('escapeHtml', FALSE)->setAttribute('template', "<a href='\${0}'>".$T("download_$type")."</a>");
    echo "</li>";
}
echo "</ul>";

echo $view->buttonList()
    ->insert($view->button('Close', $view::BUTTON_CANCEL))
;

