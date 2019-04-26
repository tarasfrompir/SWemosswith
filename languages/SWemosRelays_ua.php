<?php
$dictionary = array(
 'SWemosRelays_MODULE_NAME' => 'Простий Пристрій - Wemos вимикач',
);
foreach ($dictionary as $k => $v) {
 if (!defined('LANG_' . $k)) {
  define('LANG_' . $k, $v);
 }
}
