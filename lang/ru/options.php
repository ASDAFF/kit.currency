<?
/**
 * Copyright (c) 13/1/2021 Created By/Edited By ASDAFF asdaff.asad@yandex.ru
 */

$MESS ['CURRENCY_AUTO_UPDATE'] = "Включить автоматическое получение курсов валют?";
$MESS ['CURRENCY_AUTO_UPDATE_PERIOD'] = "Время обновления курсов (ЧЧ:ММ):";
$MESS ['CURRENCY_AUTO_UPDATE_PERIOD_DESCR'] = "Курсы валют будут обновляться автоматически.<br>Агент обновления курсов начнёт работу #start_date#.";
$MESS ['CURRENCY_AUTO_UPDATE_PERIOD_DESCR2'] = "Обновление курсов валют отключено.";
$MESS ['CURRENCY_AUTO_CURRENCIES'] = "Настройки валют:";
$MESS ['CURRENCY_AUTO_CURRENCIES_CUR'] = "Валюта";
$MESS ['CURRENCY_AUTO_CURRENCIES_CODE'] = "Код валюты (cbr)";
$MESS ['CURRENCY_AUTO_CURRENCIES_PERCENT'] = "Наценка";
$MESS ['CURRENCY_AUTO_CURRENCIES_DESCR'] = "По каждой валюте Вы можете указать её код на cbr.ru, а также наценку в процентах, на которую необходимо увеличить (+) или уменьшить (-) курс нацбанка для получения вашего курса по выбранной валюте.";
$MESS ['MAIN_RESTORE_DEFAULTS'] = "По умолчанию";
$MESS ['YES'] = "Да";
$MESS ['NO'] = "Нет";

$MESS ['CURRENCY_AUTO_UCACHE'] = "Включить автоматическую очистку управляемого кеша?";
$MESS ['CURRENCY_AUTO_UCACHE_PERIOD'] = "Время очистки кеша (ЧЧ:ММ):";
$MESS ['CURRENCY_AUTO_UCACHE_PERIOD_DESCR'] = "Управляемый кеш будет очищаться автоматически.<br>Агент очистки кеша начнёт работу #start_date#.";
$MESS ['CURRENCY_AUTO_UCACHE_PERIOD_DESCR2'] = "Автоматическая очистка управляемого кеша отключена.<br>Данная опция будет полезна, если есть необходимость очистки кеша после получения свежих курсов.";

KITCurrencyAuto::clearCache();

$MESS ['CURRENCY_AUTO_ROUND'] = "Включить округления?";
$MESS ['CURRENCY_AUTO_ROUND_0'] = "Не округлять";
$MESS ['CURRENCY_AUTO_ROUND_1'] = "Округлять до целого в большую сторону";
$MESS ['CURRENCY_AUTO_ROUND_2'] = "Округлять до целого в меньшую сторону";
$MESS ['CURRENCY_AUTO_ROUND_3'] = "Округлять по правилам округления до знака:";

$MESS ['CURRENCY_AUTO_ROUND_DIGIT_DESCR'] = "Вы можете указать знак после запятой, до которого нужно округлить курс (максимум - 4)";

$MESS ['CURRENCY_AUTO_TOMORROW'] = "Курс на завтра";
$MESS ['CURRENCY_AUTO_TOMORROW_DESCR'] = "При отметке данной настройки, модуль будет пытаться получить курсы на текущюю и на завтрашнюю дату.";

$MESS ['CURRENCY_AUTO_DEFAULT'] = "Менять курс валюты по умолчанию?";
?>