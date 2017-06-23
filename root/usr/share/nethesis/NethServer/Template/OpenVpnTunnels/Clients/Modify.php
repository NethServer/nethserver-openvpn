<?php

/* @var $view \Nethgui\Renderer\Xhtml */
$protos = array('udp' => $T('UDP'), 'tcp-client' => $T('TCP'));

if ($view->getModule()->getIdentifier() == 'update') {
    $headerText = 'update_header_label';
} else {
    $headerText = 'create_header_label';
}

echo $view->header()->setAttribute('template',$T($headerText));

if ($view->getModule()->getIdentifier() == 'update') {
    $name = $view->textInput('name', $view::STATE_READONLY);
} else {
    $name = $view->textInput('name');
}

$interfaceChoices = \Nethgui\Widget\XhtmlWidget::hashToDatasource($view['WanInterfaces']);
$wanPriorityPanel = $view->objectsCollection('WanPriority')
    ->setAttribute('key', 'id')
    ->setAttribute('ifEmpty', function ($view) use ($T) {
        return $T('NoWansDefined_label');
    })
    ->setAttribute('template', function ($view) use ($T, $interfaceChoices) {
        return $view->panel()->setAttribute('class', 'wanprio')
            ->insert($view->selector("Interface", $view::SELECTOR_DROPDOWN | $view::LABEL_NONE)
                ->setAttribute('choices', $interfaceChoices)
            );
    })
;

echo $view->panel()->insert($name);

$remote = $view->fieldset()->setAttribute('template', $T('Remote_label'))
     ->insert($view->textArea('RemoteHost', $view::LABEL_ABOVE)->setAttribute('dimensions', '5x30'))
     ->insert($view->textInput('RemotePort'))
     ->insert($view->fieldsetSwitch('WanPriorityStatus', 'enabled', $view::FIELDSETSWITCH_EXPANDABLE | $view::FIELDSETSWITCH_CHECKBOX)->setAttribute('uncheckedValue', 'disabled')
        ->insert($wanPriorityPanel)
     )
;


$auth = $view->fieldset()->setAttribute('template', $T('AuthMode_label'))
    ->insert($view->fieldsetSwitch('AuthMode', 'psk',$view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textArea('Psk', \Nethgui\Renderer\WidgetFactoryInterface::LABEL_NONE)->setAttribute('dimensions', '25x30'))
    )
    ->insert($view->fieldsetSwitch('AuthMode', 'certificate',$view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textArea('Crt', \Nethgui\Renderer\WidgetFactoryInterface::LABEL_NONE)->setAttribute('dimensions', '5x40'))
    )
    ->insert($view->fieldsetSwitch('AuthMode', 'password-certificate',$view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textInput('User'))
        ->insert($view->textInput('Password'))
        ->insert($view->textArea('Crt', \Nethgui\Renderer\WidgetFactoryInterface::LABEL_ABOVE)->setAttribute('dimensions', '5x40'))
    );

$advanced = $view->fieldset('', $view::FIELDSET_EXPANDABLE)->setAttribute('template', $T('Advanced_label'))
    ->insert($view->fieldset()->setAttribute('template', $T('Mode_label'))
        ->insert($view->radioButton('Mode', 'bridged'))
        ->insert($view->radioButton('Mode', 'routed'))
    )
    ->insert($view->selector('Protocol', $view::SELECTOR_DROPDOWN)->setAttribute('choices', \Nethgui\Widget\XhtmlWidget::hashToDatasource($protos)))
    ->insert($view->checkbox('Compression', 'enabled')->setAttribute('uncheckedValue', 'disabled'))
    ->insert($view->selector('Cipher', $view::SELECTOR_DROPDOWN));

echo $view->fieldsetSwitch('status', 'enabled',  $view::FIELDSETSWITCH_CHECKBOX)
        ->setAttribute('uncheckedValue', 'disabled')
        ->insert($remote)
        ->insert($auth)
        ->insert($advanced);

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

$view->includeCss('
.wanprio select {
    display: block;
    margin-bottom: 4px;
}
');
