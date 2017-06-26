<?php
/* @var $view \Nethgui\Renderer\Xhtml */
echo $view->header()->setAttribute('template', $T('OpenVpnTunnels_'.$view->getModule()->getIdentifier().'_title'));

$view->requireFlag($view::INSET_DIALOG);

echo $view->translate('confirm_'.$view->getModule()->getIdentifier().'_label');

echo $view->buttonList()
    ->insert($view->button('Confirm', $view::BUTTON_SUBMIT))
    ->insert($view->button('Cancel', $view::BUTTON_CANCEL))
;

