<?
class EMBXCurrencyAuto {

	function err_mess() {
		$module_id= "embx.currency";
		return "<br>Module: " . $module_id . "<br>Class: EMBXCurrencyAuto<br>File: " . __FILE__;
	}

	/**
	 * Обновление курсов
	 */
	function updateRates($handload = false){
		$options= EMBXCurrencyAuto :: getOptions();

		if($options['CURRENCY_AUTO_UPDATE'] == 0 && !$handload){
			return "EMBXCurrencyAuto::updateRates();";
		}
		if(count($options['CURRENCY_AUTO_CURRENCIES']) == 0){
			return "EMBXCurrencyAuto::updateRates();";
		}

		CModule :: IncludeModule("currency");
		
		$dates = array();
		$dates[] = date("d.m.Y");
		if($options['CURRENCY_AUTO_TOMORROW'] == "Y"){
			$dates[] = date("d.m.Y", mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		}
		
		foreach($options['CURRENCY_AUTO_CURRENCIES'] as $currency){
			foreach ($dates as $date){
				$rate = EMBXCurrencyAuto :: getRate($date, $currency["code"]);
					
				if(!empty($rate['RATE'])){
				
					if(!empty($currency["percent"])){
						$rate['RATE'] = $rate['RATE'] + $rate['RATE'] * $currency["percent"] / 100;
					}
				
					if($options['CURRENCY_AUTO_ROUND'] == 1){
						$rate['RATE'] = ceil($rate['RATE']);
					}else if($options['CURRENCY_AUTO_ROUND'] == 2){
						$rate['RATE'] = floor($rate['RATE']);
					}else if($options['CURRENCY_AUTO_ROUND'] == 3){
						$digit = ($options["CURRENCY_AUTO_ROUND_DIGIT"] > 4) ? 4 : (($options["CURRENCY_AUTO_ROUND_DIGIT"] < 0) ? 0 : $options["CURRENCY_AUTO_ROUND_DIGIT"]);
						$rate['RATE'] = round($rate['RATE'], $digit);
					}else{
						$rate['RATE'] = round($rate['RATE'], 4);
					}
				
					$arFilter = array(
							"CURRENCY" => $currency["id"],
							"DATE_RATE" => $date
					);
					$by = "date";
					$order = "desc";
				
					$db_rate = CCurrencyRates::GetList($by, $order, $arFilter);
					if($ar_rate = $db_rate->Fetch()){
						CCurrencyRates::Delete($ar_rate["ID"]);
					}
				
					$arFields = array(
							"RATE" => $rate['RATE'],
							"RATE_CNT" => $rate['RATE_CNT'],
							"CURRENCY" => $currency["id"],
							"DATE_RATE" => $date
					);
					CCurrencyRates::Add($arFields);
					
					if($options["CURRENCY_AUTO_DEFAULT"] == 1){
						CCurrency::Update($currency["id"], array("AMOUNT" => $rate['RATE']));
					}
				}				
			}	

		}

		return "EMBXCurrencyAuto::updateRates();";
	}
	
	/**
	 * Очистка кеша
	 */
	function clearCache(){		
		$options= EMBXCurrencyAuto :: getOptions();

		if($options['CURRENCY_AUTO_UCACHE'] == 0){
			return "EMBXCurrencyAuto::clearCache();";
		}
		
		$GLOBALS["CACHE_MANAGER"]->CleanAll();
		$GLOBALS["stackCacheManager"]->CleanAll();
		
		return "EMBXCurrencyAuto::clearCache();";
	}

	/**
	 * Получение курса с сайта нацбанка
	 *
	 * @param string $DATE_RATE
	 * @param string $CURRENCY
	 * @return array
	 */
	function getRate($DATE_RATE, $CURRENCY){
		global $APPLICATION, $DB, $lang;

		$QUERY_STR = "date_req=".$DB->FormatDate($DATE_RATE, CLang::GetDateFormat("SHORT", $lang), "D.M.Y");
		$strQueryText = QueryGetData("www.cbr.ru", 80, "/scripts/XML_daily.asp", $QUERY_STR, $errno, $errstr);

		$RATE_CNT = $RATE = 0;
		if (strlen($strQueryText)<=0){
			if (intval($errno)>0 || strlen($errstr)>0)
				$strError = GetMessage("ERROR_QUERY_RATE");
			else
				$strError = GetMessage("ERROR_EMPTY_ANSWER");
		} else {
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");

			$charset = "windows-1251";
			if (preg_match("/<"."\?XML[^>]{1,}encoding=[\"']([^>\"']{1,})[\"'][^>]{0,}\?".">/i", $strQueryText, $matches)){
				$charset = Trim($matches[1]);
			}
			$strQueryText = preg_replace("#<!DOCTYPE[^>]+?>#i", "", $strQueryText);
			$strQueryText = preg_replace("#<"."\\?XML[^>]+?\\?".">#i", "", $strQueryText);
			$strQueryText = $APPLICATION->ConvertCharset($strQueryText, $charset, SITE_CHARSET);

			$objXML = new CDataXML();
			$res = $objXML->LoadString($strQueryText);
			if($res !== false)
				$arData = $objXML->GetArray();
			else
				$arData = false;

			if (is_array($arData) && count($arData["ValCurs"]["#"]["Valute"])>0)
			{
				for ($j1 = 0; $j1<count($arData["ValCurs"]["#"]["Valute"]); $j1++)
				{
					if ($arData["ValCurs"]["#"]["Valute"][$j1]["#"]["CharCode"][0]["#"]==$CURRENCY)
					{
						$RATE_CNT = IntVal($arData["ValCurs"]["#"]["Valute"][$j1]["#"]["Nominal"][0]["#"]);
						$arCurrValue = str_replace(",", ".", $arData["ValCurs"]["#"]["Valute"][$j1]["#"]["Value"][0]["#"]);						
						$RATE = DoubleVal($arCurrValue);
						break;
					}
				}
			}
		}

		return array('RATE_CNT' => $RATE_CNT, 'RATE' => $RATE);
	}
	
	/**
	 * Добавление кнопки для ручной загрузки курсов
	 * @param unknown $list
	 */
	function OnAdminListDisplay(&$list)
	{	
		if($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/currencies_rates.php"){
			$list->context->items[] = array(
					"ICON" => "adm-btn",
					"TEXT" => GetMessage('EMBX_CA_LIST_BT'),
					"LINK" => $GLOBALS["APPLICATION"]->GetCurPage() . "?lang=" . LANG . "&action=load_currency_rates",
					"TITLE" => GetMessage('EMBX_CA_LIST_BT_TITLE')
			);
		}	
	}

	/**
	 * Заставляем кнопку ручной загрузки курсов работать
	 */
	function OnBeforeProlog()
	{
		if($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/currencies_rates.php" && $_SERVER["REQUEST_METHOD"] == "GET" && $_GET["action"] == "load_currency_rates")
		{
			self::updateRates(true);
		}
	}

	/**
	 * Добавление пункта меню для настроек в меню "Настройки" -> "Валюты"
	 *
	 * @param unknown_type $aGlobalMenu
	 * @param unknown_type $aModuleMenu
	 */
	function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu) {
		global $USER, $APPLICATION;

		$module_id= "embx.currency";
		$_RIGHT= $APPLICATION->GetGroupRight($module_id);

		if($_RIGHT != 'W')
			return;

		foreach($aModuleMenu as &$menu){
			if($menu['section'] == 'currency'){
				$menu['items'][] = array(
						"text" => GetMessage("EMBX_CA_INSTALL_MODULE_NAME"),
						"title" => GetMessage("EMBX_CA_INSTALL_MODULE_NAME"),
						"url" => "settings.php?mid=embx.currency&lang=".LANGUAGE_ID,
						"more_url" => array(),
				);
			}
		}
	}

	/**
	 * Свойства модуля
	 *
	 * @return array
	 */
	function getOptions() {
		$module_id= "embx.currency";

		$CURRENCY_AUTO_UPDATE= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_UPDATE');
		$CURRENCY_AUTO_UPDATE_PERIOD= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_UPDATE_PERIOD');
		$CURRENCY_AUTO_START_DATE= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_START_DATE');
		$CURRENCY_AUTO_CURRENCIES= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_CURRENCIES');
		$CURRENCY_AUTO_CURRENCIES = explode(',', $CURRENCY_AUTO_CURRENCIES);
		$CURRENCY_AUTO_ROUND= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_ROUND');	
		$CURRENCY_AUTO_ROUND_DIGIT= (int) COption :: GetOptionString($module_id, 'CURRENCY_AUTO_ROUND_DIGIT');
		$CURRENCY_AUTO_TOMORROW= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_TOMORROW');
		$CURRENCY_AUTO_DEFAULT= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_DEFAULT');
		$CURRENCY_AUTO_UCACHE= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_UCACHE');
		$CURRENCY_AUTO_UCACHE_PERIOD= COption :: GetOptionString($module_id, 'CURRENCY_AUTO_UCACHE_PERIOD');
		
		$cur = array();
		foreach ($CURRENCY_AUTO_CURRENCIES as &$currency){
			$currency = explode(":", $currency);
			$cur[$currency[0]] = array(
				'id' => $currency[0],
				'code' => (!empty($currency[1]) ? $currency[1] : $currency[0]),
				'percent' => (!empty($currency[2]) ? floatval($currency[2]) : $currency[2])
			);
		}
		
		$CURRENCY_AUTO_CURRENCIES = $cur;

		return array (
				'CURRENCY_AUTO_UPDATE' => $CURRENCY_AUTO_UPDATE,
				'CURRENCY_AUTO_UPDATE_PERIOD' => $CURRENCY_AUTO_UPDATE_PERIOD,
				'CURRENCY_AUTO_START_DATE' => $CURRENCY_AUTO_START_DATE,
				'CURRENCY_AUTO_CURRENCIES' => $CURRENCY_AUTO_CURRENCIES,
				'CURRENCY_AUTO_ADD_PERCENT' => $CURRENCY_AUTO_ADD_PERCENT,
				'CURRENCY_AUTO_ROUND' => $CURRENCY_AUTO_ROUND,
				'CURRENCY_AUTO_ROUND_DIGIT' => $CURRENCY_AUTO_ROUND_DIGIT,
				'CURRENCY_AUTO_TOMORROW' => $CURRENCY_AUTO_TOMORROW,
				'CURRENCY_AUTO_DEFAULT' => $CURRENCY_AUTO_DEFAULT,
				'CURRENCY_AUTO_UCACHE' => $CURRENCY_AUTO_UCACHE,
				'CURRENCY_AUTO_UCACHE_PERIOD' => $CURRENCY_AUTO_UCACHE_PERIOD
		);
	}
}
?>