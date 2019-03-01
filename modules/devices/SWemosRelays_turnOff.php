<?php

require_once(dirname(__FILE__)."/classes_for_addons/Wemosswitch.php");
$adress = $this->getProperty("ipaddress");
$remote = new Wemosswitch($adress);
$result = $remote->off();
echo ($result);
if ($result) {
    $this->setProperty('status', 0);
    } else {
    $this->setProperty('alive', 0);
};
