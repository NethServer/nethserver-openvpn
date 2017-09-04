<?php

/* @var $view \Nethgui\Renderer\Xhtml */
$view->requireFlag($view::FORM_ENC_MULTIPART);

$actionUrl = $view->getModuleUrl();

echo $view->header()->setAttribute('template', $T('Upload_Header'));

echo $view->fileUpload('UploadArc')->setAttribute('htmlName', 'arc');

echo $view->buttonList()
    ->insert($view->button('Upload', $view::BUTTON_SUBMIT))
    ->insert($view->button('Back', $view::BUTTON_CANCEL))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;
