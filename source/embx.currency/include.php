<?
global $DB, $MESS, $DBType;
IncludeModuleLangFile(__FILE__);

CModule::AddAutoloadClasses(
	"embx.currency",
	array(
		"EMBXCurrencyAuto" => "classes/general/currency_auto.php",
	)
);

?>