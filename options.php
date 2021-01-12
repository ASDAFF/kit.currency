<?
/**
 * Copyright (c) 13/1/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$module_id = "kit.currency";
$CAT_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($CAT_RIGHT>="R") :

global $MESS;
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/lang/", "/options.php"));

include_once($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");
include_once($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/default_option.php");

CJSCore::Init(array("jquery"));

if ($REQUEST_METHOD=="GET" && strlen($RestoreDefaults)>0 && $CAT_RIGHT=="W")
{
	COption::RemoveOption($module_id);
	$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
	while($zr = $z->Fetch())
		$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
}

$arType=array(
		"1"=> GetMessage("YES"),
		"0"=> GetMessage("NO")
);

$arTypeR=array(
		"0"=> GetMessage("CURRENCY_AUTO_ROUND_0"),
		"1"=> GetMessage("CURRENCY_AUTO_ROUND_1"),
		"2"=> GetMessage("CURRENCY_AUTO_ROUND_2"),
		"3"=> GetMessage("CURRENCY_AUTO_ROUND_3")
);

CModule :: IncludeModule("currency");
$lcur = CCurrency::GetList(($b="sort"), ($order="asc"));
$currency = array();
while($data = $lcur->Fetch()){
	if($data['CURRENCY'] != 'RUB'){
		$currency[$data['CURRENCY']] = $data['FULL_NAME'];
	}
}

$val = COption::GetOptionString($module_id, "CURRENCY_AUTO_UPDATE");
$CURRENCY_AUTO_UPDATE_PERIOD_DESCR = (!empty($val)) ? GetMessage("CURRENCY_AUTO_UPDATE_PERIOD_DESCR"): GetMessage("CURRENCY_AUTO_UPDATE_PERIOD_DESCR2");
$date_start = COption::GetOptionString($module_id, "CURRENCY_AUTO_START_DATE");
$CURRENCY_AUTO_UPDATE_PERIOD_DESCR = str_replace("#start_date#", $date_start, $CURRENCY_AUTO_UPDATE_PERIOD_DESCR);

$val = COption::GetOptionString($module_id, "CURRENCY_AUTO_UCACHE");
$CURRENCY_AUTO_UCACHE_PERIOD_DESCR= (!empty($val)) ? GetMessage("CURRENCY_AUTO_UCACHE_PERIOD_DESCR"): GetMessage("CURRENCY_AUTO_UCACHE_PERIOD_DESCR2");
$date_start_cache = COption::GetOptionString($module_id, "CURRENCY_UCACHE_START_DATE");
$CURRENCY_AUTO_UCACHE_PERIOD_DESCR= str_replace("#start_date#", $date_start_cache, $CURRENCY_AUTO_UCACHE_PERIOD_DESCR);

$arAllOptions = array(
		Array("CURRENCY_AUTO_UPDATE", GetMessage("CURRENCY_AUTO_UPDATE"), Array("selectbox", $arType)),
		Array("CURRENCY_AUTO_UPDATE_PERIOD", GetMessage("CURRENCY_AUTO_UPDATE_PERIOD"), Array("text", 5), $CURRENCY_AUTO_UPDATE_PERIOD_DESCR),
		Array("CURRENCY_AUTO_TOMORROW", GetMessage("CURRENCY_AUTO_TOMORROW"), Array("checkbox"), GetMessage("CURRENCY_AUTO_TOMORROW_DESCR")),		
		Array("CURRENCY_AUTO_CURRENCIES", GetMessage("CURRENCY_AUTO_CURRENCIES"), Array("checkboxarray", $currency), GetMessage("CURRENCY_AUTO_CURRENCIES_DESCR")),
		Array("CURRENCY_AUTO_ROUND", GetMessage("CURRENCY_AUTO_ROUND"), Array("selectbox", $arTypeR)),
		Array("CURRENCY_AUTO_ROUND_DIGIT", GetMessage("CURRENCY_AUTO_ROUND_DIGIT"), Array("text", 5), GetMessage("CURRENCY_AUTO_ROUND_DIGIT_DESCR"), "display: none"),
		Array("CURRENCY_AUTO_DEFAULT", GetMessage("CURRENCY_AUTO_DEFAULT"), Array("selectbox", $arType)),
		Array("CURRENCY_AUTO_UCACHE", GetMessage("CURRENCY_AUTO_UCACHE"), Array("selectbox", $arType)),
		Array("CURRENCY_AUTO_UCACHE_PERIOD", GetMessage("CURRENCY_AUTO_UCACHE_PERIOD"), Array("text", 5), $CURRENCY_AUTO_UCACHE_PERIOD_DESCR),
		
);

if($REQUEST_METHOD=="POST" && strlen($Update)>0)
{
	$selected_catalogs = array();
	for($i=0; $i<count($arAllOptions); $i++)
	{
		$name=$arAllOptions[$i][0];
		$val=$$name;
		
		if($name == 'CURRENCY_AUTO_CURRENCIES'){
			$val1 = array();

			foreach ($val as $k=>&$v){
				if(!empty($v["currency"])){
					foreach($v as &$vv){
					    $vv = str_replace(",", ".", $vv);
						$vv = preg_replace("#[^-\.\w]#", "", $vv);
					}
					unset($vv);				
					$val1[] = implode(":", $v);
				}
			}
			unset($v);
			$val = $val1;
		}

		if(is_array($val)){
			$val = implode(',', $val);
		}else{
			if(isset($currency_auto_default_option[$name]) && is_numeric($currency_auto_default_option[$name])){
				$val = (float) trim(str_replace(',', '.', $val));
			}else{
				$val = trim($val);
			}
		}		
		
		if($arAllOptions[$i][2][0]=="checkbox" && $val!="Y") $val="N";

		if($name == 'CURRENCY_AUTO_UPDATE_PERIOD'){
			$d = explode(":", $val);
			$d[0] = (int) @$d[0];
			$d[1] = (int) @$d[1];
			$newdate = mktime($d[0], $d[1], 0, date("m"), date("d"), date("Y"));			
			if($newdate < time()){
				$newdate = mktime($d[0], $d[1], 0, date("m"), date("d")+1, date("Y"));
			}
			$date_start = date("d.m.Y H:i:s", $newdate);

			$valOld = COption::GetOptionString($module_id, "CURRENCY_AUTO_START_DATE");
			if($valOld != $date_start){
				CAgent::RemoveAgent("KITCurrencyAuto::updateRates();", $module_id);
				CAgent::AddAgent("KITCurrencyAuto::updateRates();", $module_id, "N", 86400, $date_start, "Y", $date_start);

				COption::SetOptionString($module_id, "CURRENCY_AUTO_START_DATE", $date_start);
			}
		}
		
		if($name == 'CURRENCY_AUTO_UCACHE_PERIOD'){
			$d = explode(":", $val);
			$d[0] = (int) @$d[0];
			$d[1] = (int) @$d[1];
			$newdate = mktime($d[0], $d[1], 0, date("m"), date("d"), date("Y"));
			if($newdate < time()){
				$newdate = mktime($d[0], $d[1], 0, date("m"), date("d")+1, date("Y"));
			}
			$date_start = date("d.m.Y H:i:s", $newdate);
			
			$valOld = COption::GetOptionString($module_id, "CURRENCY_UCACHE_START_DATE");
			if($valOld != $date_start){
				CAgent::RemoveAgent("KITCurrencyAuto::clearCache();", $module_id);
				CAgent::AddAgent("KITCurrencyAuto::clearCache();", $module_id, "N", 86400, $date_start, "Y", $date_start);
				
				COption::SetOptionString($module_id, "CURRENCY_UCACHE_START_DATE", $date_start);
			}
		}

		COption::SetOptionString($module_id, $name, $val);
	}
	
	$resAN = CAdminNotify::GetList(array('ID' => 'DESC'), array('MODULE_ID'=>$module_id));
	if (intval($resAN->SelectedRowsCount())>0){
		CAdminNotify::DeleteByModule($module_id);
	}
		
	LocalRedirect($APPLICATION->GetCurPageParam());
}

$aTabs = array(
		array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
		array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$tabControl->Begin();
?>
<script>
function SelectAll(mark) {
	var form=document.forms['currency_auto_options'];
	for (i = 0; i < form.elements.length; i++) {
		var strvar = form.elements[i].type;
		if (strvar.indexOf("checkbox") != -1) {
			form.elements[i].checked = mark;
		}
	}
}

$(document).ready(function() { 
	$('#CURRENCY_AUTO_ROUND').change(function(){
		if($(this).val() == 3){
			$("#tr_CURRENCY_AUTO_ROUND_DIGIT").show();
			$("#tr_CURRENCY_AUTO_ROUND_DIGIT_DESCR").show();
		}else{
			$("#tr_CURRENCY_AUTO_ROUND_DIGIT").hide();
			$("#tr_CURRENCY_AUTO_ROUND_DIGIT_DESCR").hide();
		}
	});
	$('#CURRENCY_AUTO_ROUND').change();

	$("#CURRENCY_AUTO_ROUND_DIGIT").change(function(){
		if($(this).val() * 1 > 4){
			$(this).val(4);
		}
	});
});

</script>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&lang=<?echo LANG?>" name="currency_auto_options">
<?=bitrix_sessid_post()?>
<?
$tabControl->BeginNextTab();
?>
	<?
	for($i=0; $i<count($arAllOptions); $i++){
		$Option = $arAllOptions[$i];
		$val = COption::GetOptionString($module_id, $Option[0]);
		$type = $Option[2];

		
		$style = (!empty($Option[4])) ? $Option[4] : "";
	?>
		<tr id="tr_<?=$Option[0]?>" style="<?=$style;?>">
			<td valign="top" width="50%">
				<?	if($type[0]=="checkbox") {
						echo "<label for=\"".htmlspecialchars($Option[0])."\">".$Option[1]."</label>";
					}else{
						echo $Option[1];
					}?>
			</td>
			<td valign="top" width="50%">
				<?	if($type[0]=="checkbox"){ ?>
						<input type="checkbox" name="<?echo htmlspecialchars($Option[0])?>" id="<?echo htmlspecialchars($Option[0])?>" value="Y"<?if($val=="Y")echo " checked";?>>
				<?
					} else
					   if($type[0]=="text"){
					?>
						<input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialchars($val)?>" name="<?echo htmlspecialchars($Option[0])?>" id="<?echo htmlspecialchars($Option[0])?>">
					<? }else
							if($type[0]=="textarea"){?>
						<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialchars($Option[0])?>"><?echo htmlspecialchars($val)?></textarea>
					<? }else
							if($type[0]=="selectbox"){ ?>

							<select name="<?echo htmlspecialchars($Option[0])?>"  id="<?echo htmlspecialchars($Option[0])?>">
								<?foreach($type[1] as $k=>$v):?>
									<option value="<?=$k?>" <? if($val==$k) echo 'selected';?>><?=$v?></option>
								<?endforeach;?>
							</select>
					<? }else
							if($type[0]=="checkboxarray"){
								$val = explode(',', $val);

								$d = array();
								foreach ($val as $v){
									$d1= explode(':', $v);
									if(!isset($d1[1])){
										$d1[1] = $d1[0];
									}
									if(!isset($d1[2])){
										$d1[2] = 0;
									}
									$d[$d1[0]] = $d1;
								}
								
								$val = $d;
							?>
							<table style="min-width: 500px;">
								<tr>
									<td><?=GetMessage("CURRENCY_AUTO_CURRENCIES_CUR")?></td>
									<td><?=GetMessage("CURRENCY_AUTO_CURRENCIES_CODE")?></td>
									<td><?=GetMessage("CURRENCY_AUTO_CURRENCIES_PERCENT")?></td>
								</tr>
							<?foreach($type[1] as $k=>$v):?>
								<tr>
									<td>
										<input type="checkbox" name="<?echo htmlspecialchars($Option[0])?>[<?=$k?>][currency]" 
											id="<?echo htmlspecialchars($Option[0])?>_<?=$k?>_currency" value="<?=$k?>"<?if(isset($val[$k]))echo " checked";?>>
										<label for="<?echo htmlspecialchars($Option[0])?>_<?=$k?>_currency"><?=$v?></label><br>
									</td>
									<td>							
										<input type="text" size="5" maxlength="3" value="<?echo empty($val[$k][1]) ? $k : htmlspecialchars($val[$k][1])?>" 
											name="<?echo htmlspecialchars($Option[0])?>[<?=$k?>][code]">
									</td>
									<td>								
										<input type="text" size="5" maxlength="3" value="<?echo htmlspecialchars($val[$k][2])?>" 
											name="<?echo htmlspecialchars($Option[0])?>[<?=$k?>][percent]">
									</td>									
								</tr>
							<?endforeach;?>
							</table>
					<? } ?>
			</td>
		</tr>
		<? if(!empty($Option[3])) { ?>
		<tr id="tr_<?=$Option[0]?>_DESCR" style="<?=$style;?>">
			<td>&nbsp;</td>
			<td>
<div class="notes">
<table cellspacing="0" cellpadding="0" border="0" class="notes">
	<tbody><tr class="top">
		<td class="left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr>
		<td class="left"><div class="empty"></div></td>
		<td class="content"><?=$Option[3];?></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
	<tr class="bottom">
		<td class="left"><div class="empty"></div></td>
		<td><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr>
</tbody></table>
</div>
			</td>
		</tr>
		<? } ?>
	<? } ?>
<?
$tabControl->BeginNextTab();
?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if (confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)?>";
}
</script>
<input type="submit" <?if ($CAT_RIGHT<"W") echo "disabled" ?> name="Update" value="<?echo GetMessage("MAIN_SAVE")?>">
<input type="hidden" name="Update" value="Y">
<input type="reset" name="reset" value="<?echo GetMessage("MAIN_RESET")?>">
<input type="button" <?if ($CAT_RIGHT<"W") echo "disabled" ?> title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<?endif;?>
