<?
/**
 * Copyright (c) 13/1/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

global $MESS;
$PathInstall= str_replace("\\", "/", __FILE__);
$PathInstall= dirname($PathInstall);
IncludeModuleLangFile(dirname($PathInstall) . "/include.php");
if(is_file($PathInstall.'/version.php')){
	include($PathInstall.'/version.php');
}

if (class_exists("kit_currency"))
	return;

class kit_currency extends CModule {
	var $MODULE_ID= "kit.currency";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS= "Y";
	var $SHOW_SUPER_ADMIN_GROUP_RIGHTS= "Y";
	var $NEED_MAIN_VERSION = '11.0.0';
	var $NEED_MODULES = array('main', 'currency');

	function kit_currency() {
		$arModuleVersion= array ();

		$path= str_replace("\\", "/", __FILE__);
		$path= substr($path, 0, strlen($path) - strlen("/index.php"));

		include ($path . "/version.php");
		$this->MODULE_VERSION= $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE= $arModuleVersion["VERSION_DATE"];

		$this->PARTNER_URI = GetMessage("KIT_CA_INSTALL_MODULE_PARTNER_URI");
		$this->PARTNER_NAME = GetMessage("KIT_CA_INSTALL_MODULE_PARTNER_NAME");
		$this->MODULE_NAME= GetMessage("KIT_CA_INSTALL_MODULE_NAME");
		$this->MODULE_DESCRIPTION= GetMessage("KIT_CA_INSTALL_MODULE_DESCRIPTION");
	}

	function DoInstall() {
		global $APPLICATION;

		if (is_array($this->NEED_MODULES) && !empty($this->NEED_MODULES))
			foreach ($this->NEED_MODULES as $module)
			if (!IsModuleInstalled($module))
			$this->ShowForm('ERROR', GetMessage('KIT_CA_NEED_MODULES', array('#MODULE#' => $module)));

		$_RIGHT= $APPLICATION->GetGroupRight($this->MODULE_ID);
		if ($_RIGHT == "W") {

			if (strlen($this->NEED_MAIN_VERSION)<=0 || version_compare(SM_VERSION, $this->NEED_MAIN_VERSION)>=0) {
				$this->InstallFiles();
				$this->InstallModule();

				$arFields = array(
						'MODULE_ID' => $this->MODULE_ID,
						'MESSAGE' => GetMessage('KIT_CA_INSTALL_OK_INFO')
				);
				
				$intID = CAdminNotify::Add($arFields);
								
				$this->ShowForm('OK', GetMessage('KIT_CA_INSTALL_OK'));
			}
			else
				$this->ShowForm('ERROR', GetMessage('KIT_CA_NEED_RIGHT_VER', array('#NEED#' => $this->NEED_MAIN_VERSION)));
		}
	}

	function InstallModule(){
		RegisterModule($this->MODULE_ID);
		RegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "KITCurrencyAuto", "OnBuildGlobalMenu");
		RegisterModuleDependences("main", "OnAdminListDisplay", $this->MODULE_ID, "KITCurrencyAuto", "OnAdminListDisplay");
		RegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "KITCurrencyAuto", "OnBeforeProlog");
		
		$val = COption::GetOptionString($this->MODULE_ID, "CURRENCY_AUTO_UPDATE", "9:00");
		$d = explode(":", $val);
		$d[0] = (int) @$d[0];
		$d[1] = (int) @$d[1];
		$newdate = mktime($d[0], $d[1], 0, date("m"), date("d"), date("Y"));
		if($newdate < time()){
			$newdate = mktime($d[0], $d[1], 0, date("m"), date("d")+1, date("Y"));
		}
		$date_start = date("d.m.Y H:i:s", $newdate);		
		
		CAgent::AddAgent("KITCurrencyAuto::updateRates();", $this->MODULE_ID, "N", 86400, $date_start, "Y", $date_start);
		COption::SetOptionString($this->MODULE_ID, "CURRENCY_AUTO_START_DATE", $date_start);
		
		return true;
	}

	function InstallFiles() {
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$this->MODULE_ID."/install/themes/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/themes", true, true);

		return true;
	}

	function DoUninstall() {
		global $APPLICATION, $DB, $errors, $step;
		$_RIGHT= $APPLICATION->GetGroupRight($this->MODULE_ID);
		if ($_RIGHT == "W") {

			$this->UnInstallFiles();
			$this->UnInstallModule();

			$this->ShowForm('OK', GetMessage('KIT_CA_INSTALL_DEL'));
		}
	}

	function UnInstallModule(){
		UnRegisterModuleDependences("main", "OnBuildGlobalMenu", $this->MODULE_ID, "KITCurrencyAuto", "OnBuildGlobalMenu");
		UnRegisterModuleDependences("main", "OnAdminListDisplay", $this->MODULE_ID, "KITCurrencyAuto", "OnAdminListDisplay");
		UnRegisterModuleDependences("main", "OnBeforeProlog", $this->MODULE_ID, "KITCurrencyAuto", "OnBeforeProlog");
		
		CAgent::RemoveModuleAgents($this->MODULE_ID);
		COption::RemoveOption($this->MODULE_ID);
		UnRegisterModule($this->MODULE_ID);

		return true;
	}

	function UnInstallFiles($arParams= array ()) {
		global $DB;

		$db_res= $DB->Query("SELECT ID FROM b_file WHERE MODULE_ID = '".$this->MODULE_ID."'");
		while ($arRes= $db_res->Fetch())
			CFile :: Delete($arRes["ID"]);

		DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$this->MODULE_ID."/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/themes/.default");
		DeleteDirFilesEx("/bitrix/themes/.default/icons/".$this->MODULE_ID."/");

		return true;
	}

	private function ShowForm($type, $message, $buttonName='') {
		global $APPLICATION;
		
		$keys = array_keys($GLOBALS);

		for($i=0; $i<count($keys); $i++){
			if($keys[$i]!='i' && $keys[$i]!='GLOBALS' && $keys[$i]!='strTitle' && $keys[$i]!='filepath')
				global ${
				$keys[$i]};
		}

		$APPLICATION->SetTitle(GetMessage('KIT_CA_INSTALL_MODULE_NAME'));
		include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php');

		echo CAdminMessage::ShowMessage(array('MESSAGE' => $message, 'TYPE' => $type));

		?>
			<form action="<?= $APPLICATION->GetCurPage()?>" method="get">
				<p>
					<input type="hidden" name="lang" value="<?= LANG?>" />
					<input type="submit" value="<?= strlen($buttonName) ? $buttonName : GetMessage('MOD_BACK')?>" />
				</p>
			</form>
			<?
			include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php');
			die();
		}

	function GetModuleRightList() {
		$arr= array (
			"reference_id" => array (
				"D",
				"W"
			),
			"reference" => array (
				"[D] " . GetMessage("KIT_CA_DENIED"),
				"[W] " . GetMessage("KIT_CA_ADMIN")));
		return $arr;
	}
}
?>