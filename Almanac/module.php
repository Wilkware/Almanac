<?
/**
 * Almanac ist die Klasse für das IPS-Modul 'IPSymconAlmanac'.
 * Erweitert IPSModule 
 */
class AlmanacControl extends IPSModule
{
  /**
   * Bundeslaender IDs/Kuerzel - Array
   *
   * @access private
   * @var array Key ist die id, Value ist der Kuerzel
   */
  static $States = array(
    "1"   => "BY",  // Bayern
    "2"   => "BW",  // Baden-Württemberg
    "3"   => "NI",  // Niedersachsen
    "4"   => "BE",  // Berlin
    "5"   => "BB",  // Brandenburg
    "6"   => "HB",  // Bremen
    "7"   => "HH",  // Hamburg
    "8"   => "HE",  // Hessen
    "9"   => "MV",  // Mecklenburg-Vorpommern
    "10"  => "NW",  // Nordrhein-Westfalen
    "11"  => "RP",  // Rheinland-Pfalz
    "12"  => "SL",  // Saarland
    "13"  => "SN",  // Sachsen
    "14"  => "ST",  // Sachsen-Anhalt
    "15"  => "SH",  // Schleswig-Holstein
    "16"  => "TH"   // Thüringen
  );

  /**
   * Create.
   *
   * @access public
   */
  public function Create()
  {
    //Never delete this line!
    parent::Create();

    $this->RegisterPropertyString("State", "1");
    $this->RegisterPropertyString("BaseURL", "https://www.schulferien.eu/downloads/ical4.php");
		$this->RegisterPropertyBoolean("UpdateHoliday", true);
		$this->RegisterPropertyBoolean("UpdateVacation", true);
		$this->RegisterPropertyBoolean("UpdateDate", true);
    // Update daily timer
    $this->RegisterCyclicTimer("UpdateTimer", 0, 0, 1, 'ALMANAC_Update('.$this->InstanceID.');');
  }

  /**
   * Apply Configuration Changes.
   *
   * @access public
   */
  public function ApplyChanges()
  {
    //Never delete this line!
    parent::ApplyChanges();

    $state = $this->ReadPropertyString("State");
    $url  = $this->ReadPropertyString("BaseURL");
    $holiday  = $this->ReadPropertyBoolean("UpdateHoliday");
    $vacation = $this->ReadPropertyBoolean("UpdateVacation");
    $date = $this->ReadPropertyBoolean("UpdateDate");
    $this->SendDebug("ApplyChanges", "federal state=".$state." (".static::$States[$state]."), url=".$url.", updates=".($holiday?'Y':'N')."|".($vacation?'Y':'N')."|".($date?'Y':'N'), 0);

    $association =  Array(
    	Array(0, "Nein", "Close", 0xFF0000),
    	Array(1, "Ja",   "Ok", 0x00FF00)
    );
    $this->RegisterProfile(IPSVarType::vtBoolean, "ALMANAC.Question", "Bulb", "", "", 0, 0, 0, 0, $association);

    /*
    $association =  Array(
    	Array(1, "Montag", "", 0x0000FF),
    	Array(2, "Dienstag", "", 0x0000FF1),
    	Array(3, "Mittwoch", "", 0x0000FF),
    	Array(4, "Donnerstag", "", 0x0000FF),
    	Array(5, "Freitag", "", 0x0000FF),
    	Array(6, "Samstag", "", 0x0000FF),
    	Array(7, "Sonntag", "", 0x0000FF),
    );
    $this->RegisterProfile(IPSVarType::vtBoolean, "ALMANAC.Weekday", "Calendar", "", "", 0, 0, 0, 0, $associations);
    */
    
    // Holiday
    $this->RegisterVariable(IPSVarType::vtBoolean, "Ist Feiertag?", "IsHoliday",    "ALMANAC.Question", 1, !$holiday);
    $this->RegisterVariable(IPSVarType::vtString, "Feiertag", "Holiday", "", 10, !$holiday);
    // Urlaub
    $this->RegisterVariable(IPSVarType::vtBoolean, "Ist Ferienzeit?", "IsVacation", "ALMANAC.Question", 2, !$vacation);
    $this->RegisterVariable(IPSVarType::vtString, "Ferien", "Vacation", "", 20, !$vacation);
    // Date
    $this->RegisterVariable(IPSVarType::vtBoolean, "Ist Sommerzeit?", "IsSummer",   "ALMANAC.Question", 3, !$date);
    $this->RegisterVariable(IPSVarType::vtBoolean, "Ist Schaltjahr?", "IsLeapyear", "ALMANAC.Question", 4, !$date);
    $this->RegisterVariable(IPSVarType::vtBoolean, "Ist Wochenende?", "IsWeekend",  "ALMANAC.Question", 5, !$date);

    $this->RegisterVariable(IPSVarType::vtInteger, "Kalenderwoche", "WeekNumber",  "", 30, !$date);
    $this->RegisterVariable(IPSVarType::vtInteger, "Arbeitstage", "WorkDays",  "", 31, !$date);
    $this->RegisterVariable(IPSVarType::vtInteger, "Tage im Monat", "DaysInMonth",  "", 32, !$date);
    $this->RegisterVariable(IPSVarType::vtInteger, "Tage im Jahr",  "DaysInYear",  "", 33, !$date);
  }

  /**
   * Create the profile for the given associations.
   */
	protected function RegisterProfile($vartype, $name, $icon, $prefix = "", $suffix = "", $minvalue = 0, $maxvalue = 0, $stepsize = 0, $digits = 0, $associations = NULL)
	{
		if (!IPS_VariableProfileExists($name))
		{
			switch ($vartype)
			{
				case IPSVarType::vtBoolean:
					$this->RegisterProfileBoolean($name, $icon, $prefix, $suffix, $associations);
					break;
				case IPSVarType::vtInteger:
					$this->RegisterProfileInteger($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $stepsize, $digits, $associations);
					break;
				case IPSVarType::vtFloat:
					$this->RegisterProfileFloat($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $stepsize, $digits, $associations);
					break;
				case IPSVarType::vtString:
					$this->RegisterProfileString($name, $icon);
					break;
			}	
		}
		return $name;
	}

	protected function RegisterProfileType($name, $type)
	{
    if(!IPS_VariableProfileExists($name)) {
      IPS_CreateVariableProfile($name, $type);
    } 
    else {
      $profile = IPS_GetVariableProfile($name);
      if($profile['ProfileType'] != $type)
        throw new Exception("Variable profile type does not match for profile ".$name);
    }
  }

	protected function RegisterProfileBoolean($name, $icon, $prefix, $suffix, $asso)
	{
    $this->RegisterProfileType($name, IPSVarType::vtBoolean);
    
    IPS_SetVariableProfileIcon($name, $icon);
    IPS_SetVariableProfileText($name, $prefix, $suffix);

    if(sizeof($asso) !== 0){
      foreach($asso as $ass) {
        IPS_SetVariableProfileAssociation($name, $ass[0], $ass[1], $ass[2], $ass[3]);
      }
    }         
  }

	protected function RegisterProfileInteger($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $step, $digits, $asso)
	{
    $this->RegisterProfileType($name, IPSVarType::vtInteger);
    
    IPS_SetVariableProfileIcon($name, $icon);
    IPS_SetVariableProfileText($name, $prefix, $suffix);
    IPS_SetVariableProfileDigits($name, $digits);

    if(sizeof($asso) === 0){
      $minvalue = 0;
      $maxvalue = 0;
    } 
    IPS_SetVariableProfileValues($name, $minvalue, $maxvalue, $step);

    if(sizeof($asso) !== 0){
      foreach($asso as $ass) {
        IPS_SetVariableProfileAssociation($name, $ass[0], $ass[1], $ass[2], $ass[3]);
      }
    }         
  }

	protected function RegisterProfileFloat($name, $icon, $prefix, $suffix, $minvalue, $maxvalue, $step, $digits, $asso)
	{
    $this->RegisterProfileType($name, IPSVarType::vtFloat);
    
    IPS_SetVariableProfileIcon($name, $icon);
    IPS_SetVariableProfileText($name, $prefix, $suffix);
    IPS_SetVariableProfileDigits($name, $digits);

    if(sizeof($asso) === 0){
      $minvalue = 0;
      $maxvalue = 0;
    } 
    IPS_SetVariableProfileValues($name, $minvalue, $maxvalue, $step);

    if(sizeof($asso) !== 0){
      foreach($asso as $ass) {
        IPS_SetVariableProfileAssociation($name, $ass[0], $ass[1], $ass[2], $ass[3]);
      }
    }         
  }

	protected function RegisterProfileString($name, $icon, $prefix, $suffix)
	{
    $this->RegisterProfileType($name, IPSVarType::vtString);

    IPS_SetVariableProfileText($name, $prefix, $suffix);
		IPS_SetVariableProfileIcon($name, $icon);
  }

  /**
   * Create or delete variable.
   */
	protected function RegisterVariable($vartype, $name, $ident, $profile, $position, $delete)
	{
		if($delete == true) {
			switch ($vartype) {
				case IPSVarType::vtBoolean:
					$objId = $this->RegisterVariableBoolean($ident, $name, $profile, $position);
					break;
				case IPSVarType::vtInteger:
					$objId = $this->RegisterVariableInteger($ident, $name, $profile, $position);
					break;
				case IPSVarType::vtFloat:
					$objId = $this->RegisterVariableFloat($ident, $name, $profile, $position);
					break;
				case IPSVarType::vtString:
					$objId = $this->RegisterVariableString($ident, $name, $profile, $position);
					break;
			}	
		}
		else {
			$objId = @$this->GetIDForIdent($ident);
			if ($objId > 0) {
				$this->UnregisterVariable($ident);
			}
		}
		
		return $objId;
	}
  
  /**
   * Create the cyclic Update Timer.
   *
   * @access protected
   * @param  string $ident Name and Ident of the Timer.
   * @param  string $cId Client ID .
   */
  protected function RegisterCyclicTimer($ident, $hour, $minute, $second, $script)
	{
		$id = @$this->GetIDForIdent($ident);
		$name = $ident;
		if ($id && IPS_GetEvent($id)['EventType'] <> 1)
		{
		  IPS_DeleteEvent($id);
		  $id = 0;
		}
		if (!$id)
		{
		  $id = IPS_CreateEvent(1);
		  IPS_SetParent($id, $this->InstanceID);
		  IPS_SetIdent($id, $ident);
		}
		IPS_SetName($id, $name);
		// IPS_SetInfo($id, "Update AstroTimer");
		// IPS_SetHidden($id, true);
		IPS_SetEventScript($id, $script);
		if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");
		//IPS_SetEventCyclic($id, 0, 0, 0, 0, 0, 0);
		IPS_SetEventCyclicTimeFrom($id, $hour, $minute, $second);
		IPS_SetEventActive($id, false);
	}
  
  /**
   * Update a boolean value
   * 
   * @param string $Ident Ident of the boolean variable
   * @param bool $value Value of the boolean variable
   */
  private function SetValueBoolean(string $ident, bool $value)
  {
    $id = $this->GetIDForIdent($ident);
    SetValueBoolean($id, $value);
  }

  /**
   * Update a string value
   * 
   * @param string $Ident Ident of the boolean variable
   * @param string $value Value of the string variable
   */
  private function SetValueString(string $ident, string $value)
  {
    $id = $this->GetIDForIdent($ident);
    SetValueString($id, $value);
  }
  

  /**
   * Holt den aktuellen Feiertagskalender von http://www.schulferien.org und wertet diesen aus.
   * Setzt den Namen des aktuellen Feiertag oder 'Kein Feiertag'.
   *
   * @access private
   * @throws Exception Wenn Kalender nicht geladen werden konnte.
   */
  private function SetHoliday($state, $url)
  {
  }

  /**
   * Holt den aktuellen Ferienkalender von http://www.schulferien.org und wertet diesen aus.
   * Setzt den Namen der aktuellen Ferien oder 'Keine Ferien'.
   *
   * @access private
   * @throws Exception Wenn Kalender nicht geladen werden konnte.
   */
  private function SetVacation($state, $url)
  {
    $year = date("Y");
    $link = $url . '?land=' . $state . '&type=1&year=' . $year;
    $this->SendDebug('GET', $link, 0);
    $message = @file($link);
    
    if ($message === false) {
      throw new Exception("Cannot load iCal Data.", E_USER_NOTICE);
    }
    
    $this->SendDebug('LINES', count($message), 0);
    $vacation = "Keine Ferien";
    $count = (count($message) - 1);
    
    for ($line = 0; $line < $count; $line++) {
      if (strstr($message[$line], "SUMMARY:")) {
        $name   = trim(substr($message[$line], 8));
        $start  = trim(substr($message[$line + 1], 19));
        $end    = trim(substr($message[$line + 2], 17));
        $this->SendDebug("MESSAGE", "SUMMARY: ".$name." ,START: ".$start." ,END: ".$end, 0);
        $now = date("Ymd") . "\n";
        if (($now >= $start) and ( $now <= $end)) {
          $vacation = explode(' ', $name)[0];
          $this->SendDebug('FOUND', $vacation, 0);
        }
      }
    }
    
    $this->SetValueString("Holiday", $holiday);
    $this->SetValueBoolean("IsHoliday", ($holiday == "Keine Ferien")? false:true);
  }

  /**
   * Nutzt die date-Funktion
   *
   * @access private
   */
  private function SetDate()
  {
  }


  /**
  * This function will be available automatically after the module is imported with the module control.
  * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
  *
  * ALMANAC_Update($id);
  *
  */
  public function Update()
  {
    $state = $this->ReadPropertyString("State");
    $url  = $this->ReadPropertyString("BaseURL");
    $holiday  = $this->ReadPropertyBoolean("UpdateHoliday");
    $vacation = $this->ReadPropertyBoolean("UpdateVacation");
    $date = $this->ReadPropertyBoolean("UpdateDate");

    if($holiday == true) {
        try {
            $this->SetHoliday($state, $url);
        }
        catch (Exception $exc) {
            trigger_error($exc->getMessage(), $exc->getCode());
            $this->SendDebug('ERROR HOLIDAY', $exc->getMessage(), 0);
        }
    }
    if($vacation == true) {
        try {
            $this->SetVacation($state, $url);
        }
        catch (Exception $exc) {
            trigger_error($exc->getMessage(), $exc->getCode());
            $this->SendDebug('ERROR VACATION', $exc->getMessage(), 0);
        }
    }
    if($date == true) {
      $this->SetDate();
    }
  }
}

class IPSVarType extends stdClass
{
    const vtNone    = -1;
    const vtBoolean = 0;
    const vtInteger = 1;
    const vtFloat   = 2;
    const vtString  = 3;
}

?>