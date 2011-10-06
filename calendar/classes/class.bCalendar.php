<?
/*
All dates will be handled in UNIX time.

Notes
5/03/06
There is now a date selector on the calendar. When used it will post to variables
_month
_year

8/1/07
Updated the next and prev nav options to use a base month of m/01/Y instead of the passed in date to calculate the next and 
previous month

10/16/08
Added a way to template the header of the calendar

10/18/08
Added a class onto the td of the current day.

4/1/10
Massive overhaul to PHP5 and flexible days

Documentation notes
http://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_phpDocumentor.howto.pkg.html
images location
http://www.phpclasses.org/browse/package/3482/download/zip.html
*/

class bCalendar
{
  ##############################
  /**#@+ Static value to be used with setCalendarHeader
   */
  ##############################
  const CAL_MONTH_YEAR = "my";
  const CAL_SELECTOR = "selector";
  const CAL_PREVIOUS_MONTH = "prev";
  const CAL_NEXT_MONTH = "next";
  /**#@-*/
  
  /**#@+ @ignore
   */
  private $currentDate;
  private $currentPage;
  private $firstCalendarDay;
  private $firstSunOfMonth;
  private $headerTemplate;
  private $lastCalendarDay;
  private $linkedDates = array();
  private $monthArray = array();
  private $monthEvents = array();
  private $showMonthSelector = true;
  private $showNext = true;
  private $showPrev = true;
  // 0 (for Sunday) through 6 (for Saturday)
  private $startOn = 0;
  private $yearMin;
  private $yearMax;
  // Allowed values that the __get can return
  private $getterProperties = array('firstCalendarDay', 'lastCalendarDay');
  private $dateString = "_date";
  private $monthComboBox = "_month";
  private $yearComboBox = "_year";
  // Set the path to the css file 
  private $pathToCss = "";
  // Set the combo box to the current month. Default true
  private $setComboBoxToCurrentDate = true;
  
  /**#@-*/
  
  ##############################
  /**#@+ Set format values for Month/Year combobox using strftime
   */
  ##############################
  //values for month 
  public $combobox_month_val = "%m";
  public $combobox_month_txt = "%B";

  //values for year 
  public $combobox_year_val = "%Y";
  public $combobox_year_txt = "%Y";
  /**#@-*/

  ##############################
  /**#@+ Set format values for calendar header elements using strftime
   */
  ##############################
  public $header_cal_year = "%Y";
  public $header_cal_month = "%B";
  public $header_cal_day = "%A";
  public $cal_day = "%d";
  /**#@-*/
  
  ##############################
  /**#@+ Set value for month navigation text
   */ 
  ##############################
  public $next_arrow_text = "Next >>";
  public $previous_arrow_text = "<< Prev";
  /**#@-*/

  
  
  /**
   * The getter method for the class
   *
   * @param unknown_type $value
   * @return string
   * @ignore 
   */
  public function __get($value) {
    if(in_array($value, $this->getterProperties)) {
      return $this->$value;
    }
    return null;
  }
  
  
  /**
   * Constructor for the class. The date and startOn properties are optional.
   *
   * @param string $currentPage
   * @param string $pathToCss
   * @param string $date [optional] (date for calendar)
   * @param int $startOn [optional] (starting day 0=sunday 6=saturday)
   * @return bCalendar
   */
  function bCalendar($currentPage,$pathToCss,$date=null, $startOn=0)
  {
    $this->currentPage = $currentPage;

    $this->pathToCss = $pathToCss;
    
    $this->yearMax = strftime("%Y",strtotime("+10 years",time()));
    $this->yearMin = strftime("%Y",strtotime("-20 years",time()));
    
    //make sure startOn isn't a invalid option
    if($startOn < 7 && $startOn > -1) {
        $this->startOn = $startOn;
    }
    
    // Sets values for header template
    $this->setCalendarHeader(array(bCalendar::CAL_MONTH_YEAR, bCalendar::CAL_SELECTOR, bCalendar::CAL_PREVIOUS_MONTH, bCalendar::CAL_NEXT_MONTH));
    
    if(!empty($_REQUEST[$this->monthComboBox]))
    {
      $this->currentDate = mktime(0,0,0,$_REQUEST[$this->monthComboBox],1,$_REQUEST[$this->yearComboBox]);
    }
    elseif(!empty($_REQUEST[$this->dateString])) {
      $this->currentDate = strtotime($_REQUEST[$this->dateString]);
    }
    elseif($date)
    {
      $this->currentDate = strtotime($date);
    }
    else
    {
      $this->currentDate = time();
    }

    $this->configureMonth();

  }
  
  /**
  * This will format the header portion of the calendar. Accepts an array with the following CONST values
  *
  * bCalendar::CAL_MONTH_YEAR = month year (takes two columns)<br/>
  * bCalendar::CAL_SELECTOR = month selector (takes three columns)<br/>
  * bCalendar::CAL_PREVIOUS_MONTH = next arrow (takes one column)<br/>
  * bCalendar::CAL_NEXT_MONTH = previous arrow (takes one column)<br/>
  *
  * @param array $fields
  * @return void
  **/
  public function setCalendarHeader($fields) {
    $this->headerTemplate = implode("|", $fields);
  }
  
  /**
  * This will set the range of the year combobox in the calendar header.
  * @param int $min (1950)
  * @param int $max (2020)
  * @return void
  */
  public function setComboboxYearRange($min, $max) {
    if(is_int($min)) {
      $this->yearMin = $min;
    }
    if(is_int($max)) {
      $this->yearMax = $max;
    }
  }
  
  /**
  * Sets whether the combobox should snap to the current date on initial load
  * @param bool value
  * @return void
  */
  public function setComboBoxToCurrentDate($value) {
    if(is_bool($value)) {
      $this->comboBoxCurrentDate = $value;
    }
  }
  
  

  /**
   * This will return the date entered into the class in either unix time, or a formated date from strftime if one is given
   *
   * @param string $format
   * @return unixtime/string
   */
  public function getDate($format=false)
  {
    if(!$format)
    {
      return $this->currentDate;
    }
    else
    {
      return strftime($format,$this->currentDate);
    }
  }


  /**
   * This will draw the calendar.
   *
   * @return string
   */
  public function drawCalendar()
  {
    $content = "<table id='bCalendar' class='bCalendar'>\n";

    //draw month
    if($this->showMonthSelector)
    {
      //create the month selector
      $month = $this->getMonthComboBox($this->monthComboBox,$this->comboBoxCurrentDate);

      //create year selector
      $year = $this->getYearComboBox($this->yearComboBox,$this->comboBoxCurrentDate);

      $monthSelector = "{$month}{$year}<input class='calSubmitButton' type='submit' value='Go'>";
    }
    
    //create a base date and use this for month calculations so we don't have to worry about the 30 day bug in -1 months
    $base_month = strtotime(strftime("%m/01/%Y", $this->currentDate));

    if($this->showNext)
    {
      $showNext = "<a href='{$this->currentPage}".(strstr($this->currentPage,"?")?"&":"?").$this->dateString."=".(strftime("%Y/%m/%d",strtotime(" +1 month",$base_month)))."'>{$this->next_arrow_text}</a>";
    }

    if($this->showPrev)
    {
      $showPrev = "<a href='{$this->currentPage}".(strstr($this->currentPage,"?")?"&":"?").$this->dateString."=".(strftime("%Y/%m/%d",strtotime(" -1 month",$base_month)))."'>{$this->previous_arrow_text}</a>";
    }

    //this will template the header section of the site
    $content.="<tr class='calNavigation'>";
    $cal_pattern = explode("|", $this->headerTemplate);
    if(is_array($cal_pattern))
    {
      //keep track of what has been shown so you can't place two of the same thing in the header
      $template_tracking = array('my'=>false, 'selector'=>false, 'prev'=>false, 'next'=>false);
      $cell_count = 7;
      foreach($cal_pattern as $key=>$item)
      {
        switch(trim($item))
        {
          case bCalendar::CAL_MONTH_YEAR:
            if(!$template_tracking['my'] && $cell_count != 0)
            {
              $content .= "<td colspan='2' id='calHeaderMonth'>".strftime("{$this->header_cal_month} {$this->header_cal_year}",$this->currentDate)."</td>";
              $template_tracking['my'] = true;
              $cell_count -= 2;
            }
            break;
          case bCalendar::CAL_SELECTOR:
            if(!$template_tracking['selector'] && $cell_count != 0)
            {
              $content .= "<td colspan='3' id='calHeaderSelector'><form action='{$this->currentPage}' method='post'>{$monthSelector}</form></td>";
              $template_tracking['selector'] = true;
              $cell_count -= 3;
            }
            break;
          case bCalendar::CAL_PREVIOUS_MONTH:
            if(!$template_tracking['prev'] && $cell_count != 0)
            {
              $content .= "<td id='calHeaderPrev'>{$showPrev}</td>";
              $template_tracking['prev'] = true;
              $cell_count -= 1;
            }
            break;
          case bCalendar::CAL_NEXT_MONTH:
                if(!$template_tracking['next'] && $cell_count != 0)
                {
                  $content .= "<td id='calHeaderNext'>{$showNext}</td>";
                  $template_tracking['next'] = true;
                  $cell_count -= 1;
                }
                break;
          case "null":
          case "":
          default:
            if($cell_count != 0)
            {
                  $content .= "<td>&nbsp;</td>";
                  $cell_count -= 1;
            }
            break;
        }
      }
      
      //this will colspan the rest of the row if need be
      if($cell_count != 0)
      {
        $content .= "<td colspan='{$cell_count}'>&nbsp;</td>";
      }
      $content .= "</tr>";
    }
    
    
    //draw month
    $content .= $this->drawDays();

    $content.="</table>\n";
    
    return "<link href=\"{$this->pathToCss}\" rel=\"stylesheet\" type=\"text/css\">\n".$content;
  }
  
  /**
  * This will draw the days in the calendar
  * @return string
  * @ignore 
  */
  private function drawDays() {
    //draw days of the week
    $content ="<tr class='calDays'>\n";
    
    foreach(range(0, 6) as $key => $item) {
      $content .= "<td id='calDay".($key+1)."' class='".strtolower(strftime($this->header_cal_day, $this->monthArray[$item]))."'>\n";
      
      $content .= strftime($this->header_cal_day, $this->monthArray[$item]);
      
      $content .= "</td>\n";
    }
    $content.="</tr>\n";
    //draw days of the week
    
    //draw rows for days
    $textForDays = "";
    $row = 1;
    for($index = 0, $day = 1; $index < count($this->monthArray); $index++, $day++) {
      
      if (date("m/d/Y",$this->monthArray[$index]) == date("m/d/Y",time()) && date("m",$this->monthArray[$index])==date("m",$this->currentDate))
      {
        $id_name = "currentDay ". strtolower(strftime("%A", $this->monthArray[$index]));
        $id_day = "currentDayDayFormat";
        $id_data = "currentDayDataFormat";
      }
      elseif(date("m",$this->monthArray[$index]) == date("m",$this->currentDate))
      {
        $id_name = "currentMonth ". strtolower(strftime("%A", $this->monthArray[$index]));
        $id_day = "currentMonthDayFormat";
        $id_data = "currentMonthDataFormat";
      }
      else
      {
        $id_name = "notCurrentMonth ". strtolower(strftime("%A", $this->monthArray[$index]));
        $id_day = "notCurrentMonthDayFormat";
        $id_data = "notCurrentMonthDataFormat";
      }
      
      $textForDays .= "<td id='m_".strftime("%Y_%m_%d",$this->monthArray[$index])."' class='{$id_name}'>\n";
      $textForDays .= "<div class='{$id_day}'>";
      if(isset($this->linkedDates[$this->monthArray[$index]])) {
        $textForDays.= "<a class='linkedDate' href='{$this->linkedDates[$this->monthArray[$index]]}'>".strftime($this->cal_day,$this->monthArray[$index])."</a>";
      }
      else {
        $textForDays.= strftime($this->cal_day,$this->monthArray[$index]);
      }
      $textForDays .= "</div>\n";
      
      //this will check for an event and display it
      if(!empty($this->monthEvents[$this->monthArray[$index]]))
      {
        $textForDays.="<div class='{$id_data}'>";
        $textForDays.=$this->monthEvents[$this->monthArray[$index]];
        $textForDays.="</div>\n";
      }
      
      $textForDays .= "</td>\n";
      
      //draw each day
      if(($day%7) == 0) {
        $content.= "<tr class='calWeek".($row)."'>\n";
        $content .= $textForDays;
        $content.= "</tr>\n";
        
        //reset days text
        $textForDays = "";
        //next row
        $row++;
      }
    }
    return $content;
  }
  

  /**
   * This will add enough days to the calendar to display a months worth of days
   * @return void
   * @ignore 
   */
  private function configureMonth()
  {
    $finished=false;

    //this will find the first day of the month
    $firstDayOfMonth = mktime(0,0,0,date("m",$this->currentDate),1,date("Y",$this->currentDate));

    //this will find the date sunday starts on
    $this->firstSunOfMonth = strtotime("-".date("w",$firstDayOfMonth)." day",$firstDayOfMonth);
    
    //get the first day on the adjusted calendar
    $this->firstCalendarDay = strtotime("+{$this->startOn} days",$this->firstSunOfMonth);
    
    $movingDay = $this->firstCalendarDay;
    
    for($days = 0; !$finished; $days++) {
      // if you've stored 7 days check to see if you're on the last week of the calendar
      if(($days%7) == 0 && $days >7 && (strftime("%m", $this->currentDate) != strftime("%m", $movingDay))) {
        $finished = true;
        $this->lastCalendarDay =strtotime("-1 day", $movingDay); 
      }
      else {
        array_push($this->monthArray, $movingDay);
        $movingDay = strtotime("+1 day",$movingDay);
      }
    }
  }

  /**
   * Use this to add a date to the calendar: pass in the date and details for the event
   *
   * @param string $date (date of the event)
   * @param string $details (html to display)
   * @return bool
   */
  public function addEvent($date,$details)
  {
    if($date)
    {
      //just store the m/d/y of the date
      $date = strtotime(date("m/d/Y",strtotime($date)));
      
      //check to see if the date exists. if not then create the spot
      if(empty($this->monthEvents[$date])) $this->monthEvents[$date] = "";
      
      $this->monthEvents[$date].= $details;
      return true;
    }
    else
    {
      return false;
    }
  }
  /**
  * This will link the date on a calendar to a URL. 
  * @param string $date
  * @param string $url
  * @return void
  */
  public function linkDate($date, $url) { 
    $unixDate = strtotime(date("m/d/Y", strtotime($date)));
    
    $this->linkedDates[$unixDate] = $url;
  }

  /**
   * This will draw the month selector: pass in the name and wither you want it to auto select the month
   *
   * @param string $name
   * @param bool $select_current_month
   * @return string
   * @ignore 
   */
  private function getMonthComboBox($name,$select_current_month=false)
  {
    $month_range = "<select name='$name' id='$name'>\n";
    foreach (range(1,12) as $i)
    {
      $selected = "";
      if(!empty($_REQUEST[$name]))
      {
        $selected = ($i == $_REQUEST[$name]) ? "selected='selected'" : "";
      }
      elseif($select_current_month)
      {
        $selected = ($i == date("n",time())) ? "selected='selected'" : "";
      }
      $month_range .= "<option value='".strftime($this->combobox_month_val,mktime(0,0,0,$i,01,2000))."' $selected>".strftime($this->combobox_month_txt,mktime(0,0,0,$i,01,2000))."\n";
    }
    $month_range .= "</select>\n";

    return $month_range;
  }


  /**
   * This will draw the year selector: pass in the name and wither you want it to auto select the yesr
   *
   * @param string $name
   * @param bool $select_current_year
   * @return string
   * @ignore 
   */
  private function getYearComboBox($name,$select_current_year=false)
  {
    $year_range = "<select name='$name' id='$name'>\n";
    foreach(range($this->yearMin , $this->yearMax) as $i)
    {
      $selected = "";
      if(!empty($_REQUEST[$name]))
      {
        $selected = ($i == $_REQUEST[$name]) ? "selected='selected'" : "";
      }
      elseif($select_current_year)
      {
        $selected = ($i == date("Y",time())) ? "selected='selected'" : "";
      }
      $year_range .= "<option value='".strftime($this->combobox_year_val,mktime(0,0,0,10,1,$i))."' $selected>".strftime($this->combobox_year_txt,mktime(0,0,0,10,1,$i))."\n";
    }
    $year_range .= "</select>\n";

    return $year_range;
  }

}//0
?>
