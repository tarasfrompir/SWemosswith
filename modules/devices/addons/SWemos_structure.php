<?php
 
$this->device_types['wemos_relay'] = array(
        'TITLE'=>'Выключатель Wemos',
        'PARENT_CLASS'=>'SDevices',
        'CLASS'=>'SWemosRelays',
        'PROPERTIES'=>array(
	    'groupEco'=>array('DESCRIPTION'=>LANG_DEVICES_GROUP_ECO,'_CONFIG_TYPE'=>'yesno','_CONFIG_HELP'=>'SdGroupEco'),
            'groupEcoOn'=>array('DESCRIPTION'=>LANG_DEVICES_GROUP_ECO_ON,'_CONFIG_TYPE'=>'yesno','_CONFIG_HELP'=>'SdGroupEcoOn'),
            'groupSunrise'=>array('DESCRIPTION'=>LANG_DEVICES_GROUP_SUNRISE,'_CONFIG_TYPE'=>'yesno','_CONFIG_HELP'=>'SdGroupSunrise'),
            'isActivity'=>array('DESCRIPTION'=>LANG_DEVICES_IS_ACTIVITY,'_CONFIG_TYPE'=>'yesno','_CONFIG_HELP'=>'SdIsActivity'),
            'loadType'=>array('DESCRIPTION'=>LANG_DEVICES_LOADTYPE,
                '_CONFIG_TYPE'=>'select','_CONFIG_HELP'=>'SdLoadType',
                '_CONFIG_OPTIONS'=>'light='.LANG_DEVICES_LOADTYPE_LIGHT.
                    ',heating='.LANG_DEVICES_LOADTYPE_HEATING.
                    ',vent='.LANG_DEVICES_LOADTYPE_VENT.
                    ',curtains='.LANG_DEVICES_LOADTYPE_CURTAINS.
                    ',gates='.LANG_DEVICES_LOADTYPE_GATES.
                    ',power='.LANG_DEVICES_LOADTYPE_POWER),
            'icon'=>array('DESCRIPTION'=>LANG_IMAGE,'_CONFIG_TYPE'=>'style_image','_CONFIG_HELP'=>'SdIcon'),
            'ipaddress'=>array('DESCRIPTION'=>'IP адрес устройства', '_CONFIG_TYPE'=>'text', 'KEEP_HISTORY'=>0, 'DATA_KEY'=>1),
            'UPNPADDRESS'=>array('DESCRIPTION'=>'Адрес управления устройством', 'KEEP_HISTORY'=>0, 'DATA_KEY'=>1),
            'UUID'=>array('DESCRIPTION'=>'ИД устройства', 'KEEP_HISTORY'=>0, 'DATA_KEY'=>1),
		
            ),
        'METHODS'=>array(
            'turnOn'=>array('DESCRIPTION'=>LANG_DEVICES_TURN_ON,'_CONFIG_SHOW'=>1),
            'turnOff'=>array('DESCRIPTION'=>LANG_DEVICES_TURN_OFF,'_CONFIG_SHOW'=>1),
            'switch'=>array('DESCRIPTION'=>'Switch'),
        )
);
        
@include_once(ROOT . 'languages/SMagXXXdevice_' . SETTINGS_SITE_LANGUAGE . '.php');
@include_once(ROOT . 'languages/SMagXXXdevice_default' . '.php');
