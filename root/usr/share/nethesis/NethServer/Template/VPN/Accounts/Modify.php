<?php

$panel = $view->panel();
if ($view->getModule()->getIdentifier() == 'update') {
    $headerText = 'update_header_label';
    $panel->insert($view->textInput('name', $view::STATE_READONLY | $view::STATE_DISABLED));
} else {
    $headerText = 'create_header_label';
    $panel->insert($view->fieldsetSwitch('AccountType', 'vpn', $view::FIELDSET_EXPANDABLE)
            ->insert($view->textInput('name')));
    $panel->insert($view->fieldsetSwitch('AccountType', 'user',  $view::FIELDSET_EXPANDABLE)
            ->insert($view->selector('User', $view::SELECTOR_DROPDOWN)));

/*    $panel->insert($view->fieldset()->setAttribute('template', $T('AccountType_label'))
        ->insert($view->fieldsetSwitch('AccountType', 'vpn', $view::FIELDSETSWITCH_EXPANDABLE)
            ->insert($view->textInput('name')))
        ->insert($view->fieldsetSwitch('AccountType', 'user', $view::FIELDSETSWITCH_EXPANDABLE)
            ->insert($view->selector('User', $view::SELECTOR_DROPDOWN))));*/
}


$panel->insert($view->fieldset()->setAttribute('template', $T('RemoteNetwork_label'))
        ->insert($view->textInput('VPNRemoteNetwork'))
        ->insert($view->textInput('VPNRemoteNetmask')));
   
echo $view->header()->setAttribute('template', $T($headerText));
echo $panel;

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

