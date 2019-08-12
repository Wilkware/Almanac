<?php

require_once __DIR__.'/../libs/traits.php';  // Allgemeine Funktionen

class AlmanacModul extends IPSModule
{
    use TimerHelper, DebugHelper;
    /**
     * Bundeslaender IDs/Kuerzel - Array.
     *
     * @var array Key ist die id, Value ist der Kuerzel
     */
    public static $States = [
        '1'   => 'BY',  // Bayern
        '2'   => 'BW',  // Baden-Württemberg
        '3'   => 'NI',  // Niedersachsen
        '4'   => 'BE',  // Berlin
        '5'   => 'BB',  // Brandenburg
        '6'   => 'HB',  // Bremen
        '7'   => 'HH',  // Hamburg
        '8'   => 'HE',  // Hessen
        '9'   => 'MV',  // Mecklenburg-Vorpommern
        '10'  => 'NW',  // Nordrhein-Westfalen
        '11'  => 'RP',  // Rheinland-Pfalz
        '12'  => 'SL',  // Saarland
        '13'  => 'SN',  // Sachsen
        '14'  => 'ST',  // Sachsen-Anhalt
        '15'  => 'SH',  // Schleswig-Holstein
        '16'  => 'TH',   // Thüringen
    ];

    /**
     * Create.
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('State', '1');
        $this->RegisterPropertyString('BaseURL', 'https://www.schulferien.eu/downloads/ical4.php');
        $this->RegisterPropertyBoolean('UpdateHoliday', true);
        $this->RegisterPropertyBoolean('UpdateVacation', true);
        $this->RegisterPropertyBoolean('UpdateDate', true);
        // Register daily update timer
        $this->RegisterTimer('UpdateTimer', 0, 'ALMANAC_Update('.$this->InstanceID.');');   
    }

    /**
     * Apply Configuration Changes.
     */
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $state = $this->ReadPropertyString('State');
        $url = $this->ReadPropertyString('BaseURL');
        $holiday = $this->ReadPropertyBoolean('UpdateHoliday');
        $vacation = $this->ReadPropertyBoolean('UpdateVacation');
        $date = $this->ReadPropertyBoolean('UpdateDate');
        $this->SendDebug('ApplyChanges', 'federal state='.$state.' ('.static::$States[$state].'), url='.$url.', updates='.($holiday ? 'Y' : 'N').'|'.($vacation ? 'Y' : 'N').'|'.($date ? 'Y' : 'N'), 0);

        $association = [
            [0, 'Nein', 'Close', 0xFF0000],
            [1, 'Ja',   'Ok', 0x00FF00],
        ];
        $this->RegisterProfile(vtBoolean, 'ALMANAC.Question', 'Bulb', '', '', 0, 0, 0, 0, $association);

        /*
        $association = [
            [1, "Montag", "", 0x0000FF],
            [2, "Dienstag", "", 0x0000FF],
            [3, "Mittwoch", "", 0x0000FF],
            [4, "Donnerstag", "", 0x0000FF],
            [5, "Freitag", "", 0x0000FF],
            [6, "Samstag", "", 0x0000FF],
            [7, "Sonntag", "", 0x0000FF],
        ];
        $this->RegisterProfile(IPSVarType::vtBoolean, 'ALMANAC.Weekday', 'Calendar', '', '', 0, 0, 0, 0, $associations);
        */

        // Holiday(Ferien)
        $this->MaintainVariable('IsHoliday', 'Ist Feiertag?',vtBoolean, 'ALMANAC.Question', 1, $holiday);
        $this->MaintainVariable('Holiday', 'Feiertag',vtString, '', 10, $holiday);
        // Vacation(Urlaub)
        $this->MaintainVariable('IsVacation', 'Ist Ferienzeit?',vtBoolean, 'ALMANAC.Question', 2, $vacation);
        $this->MaintainVariable('Vacation', 'Ferien',vtString, '', 20, $vacation);
        // Date
        $this->MaintainVariable('IsSummer', 'Ist Sommerzeit?',vtBoolean, 'ALMANAC.Question', 3, $date);
        $this->MaintainVariable('IsLeapyear', 'Ist Schaltjahr?',vtBoolean, 'ALMANAC.Question', 4, $date);
        $this->MaintainVariable('IsWeekend', 'Ist Wochenende?',vtBoolean, 'ALMANAC.Question', 5, $date);
        $this->MaintainVariable('WeekNumber', 'Kalenderwoche',vtInteger, '', 30, $date);
        $this->MaintainVariable('DaysInMonth', 'Tage im Monat',vtInteger, '', 32, $date);
        $this->MaintainVariable('DayOfYear', 'Tag im Jahr',vtInteger, '', 33, $date);
        // Calculate next update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 1);
    }

    /**
     * Update a boolean value.
     *
     * @param string $Ident Ident of the boolean variable
     * @param bool   $value Value of the boolean variable
     */
    private function SetValueBoolean(string $ident, bool $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueBoolean($id, $value);
    }

    /**
     * Update a string value.
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
     * Update a integer value.
     *
     * @param string $Ident Ident of the boolean variable
     * @param int    $value Value of the string variable
     */
    private function SetValueInteger(string $ident, int $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueInteger($id, $value);
    }

    /**
     * Gets the actual holiday calendar from  http://www.schulferien.eu and extract the current values.
     * Pass the name of the current holiday or 'Kein Feiertag'.
     *
     * @throws Exception if calendar could not loaded.
     */
    private function SetHoliday($state, $url)
    {
        $year = date('Y');
        $link = $url.'?land='.static::$States[$state].'&type=0&year='.$year;
        $this->SendDebug('GET', $link, 0);
        $message = @file($link);

        if ($message === false) {
            throw new Exception('Cannot load iCal Data.', E_USER_NOTICE);
        }

        $this->SendDebug('LINES', count($message), 0);
        $holiday = 'Kein Feiertag';
        $count = (count($message) - 1);

        for ($line = 0; $line < $count; $line++) {
            if (strstr($message[$line], 'SUMMARY:')) {
                $name = trim(substr($message[$line], 8));
                $start = trim(substr($message[$line + 1], 19));
                $end = trim(substr($message[$line + 2], 17));
                $this->SendDebug('MESSAGE', 'SUMMARY: '.$name.' ,START: '.$start.' ,END: '.$end, 0);
                $now = date('Ymd')."\n";
                if (($now >= $start) and ($now <= $end)) {
                    $pos = strpos($name, '(Bankfeiertag)');
                    if ($pos > 0) {
                        $holiday = substr($name, 0, $pos - 1);
                    } else {
                        $holiday = $name;
                    }
                    $this->SendDebug('FOUND', $holiday, 0);
                }
            }
        }

        $this->SetValueString('Holiday', $holiday);
        $this->SetValueBoolean('IsHoliday', ($holiday == 'Kein Feiertag') ? false : true);
    }

    /**
     * Gets the actual vacation calendar from  http://www.schulferien.eu and extract the current values.
     * Pass the name of the current vacation or 'Keine Ferien'.
     *
     * @throws Exception if calendar could not loaded.
     */
    private function SetVacation($state, $url)
    {
        if ((int) date('md') < 110) {
            $year = date('Y') - 1;
            $link = $url.'?land='.$state.'&type=1&year='.$year;
            $this->SendDebug('GET', $link, 0);
            $message0 = @file($link);
            if ($message0 === false) {
                throw new Exception('Cannot load iCal Data.', E_USER_NOTICE);
            }
            $this->SendDebug('LINES', count($message0), 0);
        } else {
            $message0 = [];
        }

        $year = date('Y');
        $link = $url.'?land='.$state.'&type=1&year='.$year;
        $this->SendDebug('GET', $link, 0);
        $message1 = @file($link);

        if ($message1 === false) {
            throw new Exception('Cannot load iCal Data.', E_USER_NOTICE);
        }
        $this->SendDebug('LINES', count($message1), 0);

        $message = array_merge($message0, $message1);

        $vacation = 'Keine Ferien';
        $count = (count($message) - 1);

        for ($line = 0; $line < $count; $line++) {
            if (strstr($message[$line], 'SUMMARY:')) {
                $name = trim(substr($message[$line], 8));
                $start = trim(substr($message[$line + 1], 19));
                $end = trim(substr($message[$line + 2], 17));
                $this->SendDebug('MESSAGE', 'SUMMARY: '.$name.' ,START: '.$start.' ,END: '.$end, 0);
                $now = date('Ymd')."\n";
                if (($now >= $start) and ($now <= $end)) {
                    $vacation = explode(' ', $name)[0];
                    $this->SendDebug('FOUND', $vacation, 0);
                }
            }
        }

        $this->SetValueString('Vacation', $vacation);
        $this->SetValueBoolean('IsVacation', ($vacation == 'Keine Ferien') ? false : true);
    }

    /**
     * Use the PHP-date function to extract some useful informations.
     */
    private function SetDate()
    {
        $this->SetValueBoolean('IsSummer', date('I'));
        $this->SetValueBoolean('IsLeapyear', date('L'));
        $this->SetValueBoolean('IsWeekend', date('N') > 5);

        $this->SetValueInteger('WeekNumber', idate('W'));
        $this->SetValueInteger('DaysInMonth', idate('t'));
        $this->SetValueInteger('DayOfYear', idate('z') + 1);
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_Update($id);
     */
    public function Update()
    {
        $state = $this->ReadPropertyString('State');
        $url = $this->ReadPropertyString('BaseURL');
        $holiday = $this->ReadPropertyBoolean('UpdateHoliday');
        $vacation = $this->ReadPropertyBoolean('UpdateVacation');
        $date = $this->ReadPropertyBoolean('UpdateDate');

        if ($holiday == true) {
            try {
                $this->SetHoliday($state, $url);
            } catch (Exception $exc) {
                trigger_error($exc->getMessage(), $exc->getCode());
                $this->SendDebug('ERROR HOLIDAY', $exc->getMessage(), 0);
            }
        }
        if ($vacation == true) {
            try {
                $this->SetVacation($state, $url);
            } catch (Exception $exc) {
                trigger_error($exc->getMessage(), $exc->getCode());
                $this->SendDebug('ERROR VACATION', $exc->getMessage(), 0);
            }
        }
        if ($date == true) {
            $this->SetDate();
        }

        // calculate next update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 10, 0);
    }
}
