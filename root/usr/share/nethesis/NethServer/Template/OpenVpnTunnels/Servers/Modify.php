<?php


$protos = array('udp' => $T('UDP'), 'tcp-server' => $T('TCP'));

echo $view->textInput('name', ($view->getModule()->getIdentifier() == 'update' ? $view::STATE_READONLY : 0));

echo $view->fieldsetSwitch('status', 'enabled',  $view::FIELDSETSWITCH_CHECKBOX)
        ->setAttribute('uncheckedValue', 'disabled')


     ->insert($view->textArea('PublicAddresses', $view::LABEL_ABOVE)->setAttribute('dimensions', '5x30'))
     ->insert($view->textInput('Port'))
     ->insert($view->fieldset() ->setAttribute('template', $T('Topology_label'))
         ->insert($view->fieldsetSwitch('Topology', 'subnet', $view::FIELDSETSWITCH_EXPANDABLE)
             ->insert($view->textInput('Network'))
         )
         ->insert($view->fieldsetSwitch('Topology', 'p2p', $view::FIELDSETSWITCH_EXPANDABLE)
             ->insert($view->textInput('LocalPeer'))
             ->insert($view->textInput('RemotePeer'))
             ->insert($view->fieldset() ->setAttribute('template', $T('Authentication_label'))
                 ->insert($view->textArea('Psk', $view::LABEL_ABOVE)->setAttribute('dimensions', '25x30'))
             )
         )
     )

     ->insert($view->fieldset() ->setAttribute('template', $T('Routes_label'))
         ->insert($view->columns()
             # left column
             ->insert($view->textArea('LocalNetworks', $view::LABEL_ABOVE)->setAttribute('dimensions', '5x30'))
             # right column
             ->insert($view->textArea('RemoteNetworks', $view::LABEL_ABOVE)->setAttribute('dimensions', '5x30'))
          )
    )

    ->insert($view->fieldset('', $view::FIELDSET_EXPANDABLE)->setAttribute('template', $T('Advanced_label'))
        ->insert($view->selector('Protocol', $view::SELECTOR_DROPDOWN)->setAttribute('choices', \Nethgui\Widget\XhtmlWidget::hashToDatasource($protos)))
        ->insert($view->checkbox('Compression','enabled')->setAttribute('uncheckedValue', 'disabled'))
        ->insert($view->selector('Cipher', $view::SELECTOR_DROPDOWN))
    )
;

$buttonList = $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

echo $buttonList;
