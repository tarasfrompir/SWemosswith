<?php
$dictionary = array(
 'SWemosRelays_MODULE_NAME' => 'Wemos Relay',
 'SWemosRelays_STRUCTURE_NAME' => 'Switch from Wemos',
);
foreach ($dictionary as $k => $v) {
 if (!defined('LANG_' . $k)) {
  define('LANG_' . $k, $v);
 }
}
