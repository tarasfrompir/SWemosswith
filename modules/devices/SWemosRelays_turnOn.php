<?php

require_once(dirname(__FILE__)."/classes_for_addons/Wemosswitch.php");
$adress = $this->getProperty("UPNPADDRESS");
$remote = new Wemosswitch($adress);
$result = $remote->on();
echo $result;
if ($result) {
    $this->setProperty('status', 1);
    } else {
    $this->setProperty('alive', 0);
};
