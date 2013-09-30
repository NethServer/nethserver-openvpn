<?php

echo $view->fieldsetSwitch('ServerStatus', 'enabled',  $view::FIELDSETSWITCH_CHECKBOX)
        ->setAttribute('uncheckedValue', 'disabled')
    ->insert($view->selector('AuthMode'))
    ->insert($view->fieldsetSwitch('Mode', 'routed', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textInput('Network'))
        ->insert($view->textInput('Netmask'))
        ->insert($view->selector('RouteToVPN'))
        ->insert($view->selector('ClientToClient')))
    ->insert($view->fieldsetSwitch('Mode', 'bridged', $view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textInput('BridgeStartIP'))
        ->insert($view->textInput('BridgeEndIP')));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
