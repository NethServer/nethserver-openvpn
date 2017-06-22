<?php

echo $view->fieldsetSwitch('ServerStatus', 'enabled',  $view::FIELDSETSWITCH_CHECKBOX)
        ->setAttribute('uncheckedValue', 'disabled')
     ->insert($view->selector('AuthMode'))
     ->insert($view->fieldset()
        ->setAttribute('template', $T('Mode'))

         ->insert($view->fieldsetSwitch('Mode', 'routed', $view::FIELDSETSWITCH_EXPANDABLE)
             ->insert($view->textInput('Network'))
             ->insert($view->textInput('Netmask'))
             ->insert($view->fieldset('', $view::FIELDSET_EXPANDABLE)->setAttribute('template', $T('RoutedAdvanced_label'))
                 ->insert($view->checkbox('RouteToVPN', 'enabled')->setAttribute('uncheckedValue', 'disabled'))
                 ->insert($view->checkbox('ClientToClient', 'enabled')->setAttribute('uncheckedValue', 'disabled'))))
             ->insert($view->fieldsetSwitch('Mode', 'bridged', $view::FIELDSETSWITCH_EXPANDABLE)
                 ->insert($view->selector('Bridge', $view::SELECTOR_DROPDOWN))
                 ->insert($view->textInput('BridgeStartIP'))
                 ->insert($view->textInput('BridgeEndIP'))))
    ->insert($view->fieldset('', $view::FIELDSET_EXPANDABLE)->setAttribute('template', $T('Advanced_label'))
        ->insert($view->checkbox('Compression','enabled')->setAttribute('uncheckedValue', 'disabled'))
        ->insert($view->checkbox('PushExtraRoutes','enabled')->setAttribute('uncheckedValue', 'disabled'))
     )
    ->insert($view->fieldset()
        ->setAttribute('template', $T('Connection_label'))
        ->insert($view->textInput('Remote')->setAttribute('placeholder', $view['RemoteDefault']))
        ->insert($view->textInput('port')))
;
echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
