<?
/**
 * Copyright (c) 13/1/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

global $DB, $MESS, $DBType;
IncludeModuleLangFile(__FILE__);

CModule::AddAutoloadClasses(
	"kit.currency",
	array(
		"KITCurrencyAuto" => "classes/general/currency_auto.php",
	)
);

?>