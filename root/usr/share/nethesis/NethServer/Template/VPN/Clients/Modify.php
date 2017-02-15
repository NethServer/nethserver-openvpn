<?php

/* @var $view \Nethgui\Renderer\Xhtml */

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

echo $view->panel()
    ->insert($name)
    ->insert($view->hidden('VPNType'))
    ->insert($view->textInput('RemoteHost'))
    ->insert($view->textInput('RemotePort'))
    ->insert($view->checkbox('Compression', 'enabled')->setAttribute('uncheckedValue', 'disabled'))
    ->insert($view->fieldset()->setAttribute('template', $T('Mode_label'))
        ->insert($view->radioButton('Mode', 'bridged'))
        ->insert($view->radioButton('Mode', 'routed'))
    )
;

echo $view->fieldset()->setAttribute('template', $T('AuthMode_label'))
    ->insert($view->fieldsetSwitch('AuthMode', 'certificate',$view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textArea('Crt', \Nethgui\Renderer\WidgetFactoryInterface::LABEL_NONE)->setAttribute('dimensions', '5x40'))
    )
    ->insert($view->fieldsetSwitch('AuthMode', 'password-certificate',$view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textInput('User'))
        ->insert($view->textInput('Password'))
        ->insert($view->textArea('Crt', \Nethgui\Renderer\WidgetFactoryInterface::LABEL_ABOVE)->setAttribute('dimensions', '5x40'))
    )
    ->insert($view->fieldsetSwitch('AuthMode', 'psk',$view::FIELDSETSWITCH_EXPANDABLE)
        ->insert($view->textArea('Psk', \Nethgui\Renderer\WidgetFactoryInterface::LABEL_NONE)->setAttribute('dimensions', '5x40'))
    );

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

