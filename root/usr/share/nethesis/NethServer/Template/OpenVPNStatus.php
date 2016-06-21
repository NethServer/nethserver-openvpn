<?php
$view->includeCSS("
  div.vpn-item {
    padding: 10px;
    border: 1px solid #ccc;
    max-width: 400px;
  }
  .vpn-item dt {
    float: left;
    clear: left;
    text-align: right;
    font-weight: bold;
    margin-right: 0.5em;
    padding: 0.1em;
  }
  .vpn-item dt:after {
    content: \":\";
  }
  .vpn-item dd {
    padding: 0.1em;
  }
  .vpn-container h2, .vpn-item h2 {
    font-weight: bold;
    font-size: 120%;
    text-align: center;
    padding: 0.5em;
  }
  .vpn-item pre {
      margin-top: 2px;
      padding: 2px;
  }
");


foreach($view->getModule()->getChildren() as $child) {
    echo $view->inset($child->getIdentifier());
}
