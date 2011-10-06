<?
error_reporting(E_ALL);

// Update the language
//setlocale(LC_ALL, 'Japanese');
//http://www.w3.org/WAI/ER/IG/ert/iso639.htm

include_once "classes/class.bCalendar.php";

//figure out what date we're using
//$date = (!empty($_REQUEST['date']) ? $_REQUEST['date'] : date("m/d/Y",time()));
$obj_cal = new bCalendar("index.php","css/calStyle.css"/*, $date, 1*/);

//optional parameters
$obj_cal->setComboboxYearRange(2000,2010);
//$obj_cal->setCalendarHeader(array(bCalendar::CAL_PREVIOUS_MONTH,bCalendar::CAL_MONTH_YEAR,bCalendar::CAL_SELECTOR,bCalendar::CAL_NEXT_MONTH));
$obj_cal->linkDate("10/15/2010", "http://google.com");
$obj_cal->linkDate("10/27/2010", "http://joystiq.com");
$obj_cal->addEvent("10/19/2010", "This is a test event");
$obj_cal->setComboBoxToCurrentDate(false);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd"> 
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--<meta http-equiv="Content-Type" content="text/html; charset=EUC-JP" /> 
<meta http-equiv="Content-Language" content="ja" />-->
<title>152.org [calendar]</title>
</head>
<body>
<div style="margin: 50px auto; width: 600px; ">
<?
echo "first day: ".date("m/d/Y", $obj_cal->firstCalendarDay)."<br>";
echo "last day: ".date("m/d/Y", $obj_cal->lastCalendarDay)."<br>";
?>
<?=$obj_cal->drawCalendar();?>
</div>

</body>
</html>
