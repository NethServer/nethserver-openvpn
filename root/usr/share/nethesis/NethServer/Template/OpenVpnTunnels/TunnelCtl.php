<?php
/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->header('service')->setAttribute('template', $T('OpenVpnTunnels_'.$view->getModule()->getIdentifier().'_title'));

$view->requireFlag($view::INSET_DIALOG);

$message = $view->getModule()->getIdentifier() === 'enable' ? $T('confirm_enable_label') : $T('confirm_disable_label');
echo $view->textLabel('service')->setAttribute('template', $message);

echo $view->buttonList()
    ->insert($view->button('Confirm', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;

