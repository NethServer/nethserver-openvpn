<?php

/* @var $view \Nethgui\Renderer\Xhtml */
$view->rejectFlag($view::INSET_FORM);

$actionUrl = $view->getModuleUrl();

echo "<form action=\"{$actionUrl}\" method=\"post\" enctype=\"multipart/form-data\">";

echo $view->header()->setAttribute('template', $T('Upload_Header'));

$idArc = $view->getUniqueId('arc');

echo "<div class=\"labeled-control label-above\"><label for=\"{$idArc}\">" . \htmlspecialchars($T('UploadArc_label')) . "</label><input type=\"file\" name=\"arc\" id=\"{$idArc}\" /></div>";

echo $view->buttonList()
    ->insert($view->button('Upload', $view::BUTTON_SUBMIT))
    ->insert($view->button('Back', $view::BUTTON_CANCEL))
    ->insert($view->button('Help', $view::BUTTON_HELP))
;

echo "</form>";
