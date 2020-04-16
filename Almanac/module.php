<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/traits.php';  // Allgemeine Funktionen

class AlmanacModule extends IPSModule
{
    use ProfileHelper;
    use TimerHelper;
    use DebugHelper;
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
        $this->RegisterTimer('UpdateTimer', 0, 'ALMANAC_Update(' . $this->InstanceID . ');');
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
        $this->SendDebug('ApplyChanges', 'federal state=' . $state . ' (' . static::$States[$state] . '), url=' . $url . ', updates=' . ($holiday ? 'Y' : 'N') . '|' . ($vacation ? 'Y' : 'N') . '|' . ($date ? 'Y' : 'N'), 0);

        $association = [
            [0, 'Nein', 'Close', 0xFF0000],
            [1, 'Ja',   'Ok', 0x00FF00],
        ];
        $this->RegisterProfile(vtBoolean, 'ALMANAC.Question', 'Bulb', '', '', 0, 0, 0, 0, $association);

        // Holiday (Feiertage)
        $this->MaintainVariable('IsHoliday', 'Ist Feiertag?', vtBoolean, 'ALMANAC.Question', 1, $holiday);
        $this->MaintainVariable('Holiday', 'Feiertag', vtString, '', 10, $holiday);
        // Vacation (Schulferien)
        $this->MaintainVariable('IsVacation', 'Ist Ferienzeit?', vtBoolean, 'ALMANAC.Question', 2, $vacation);
        $this->MaintainVariable('Vacation', 'Ferien', vtString, '', 20, $vacation);
        // Date
        $this->MaintainVariable('IsSummer', 'Ist Sommerzeit?', vtBoolean, 'ALMANAC.Question', 3, $date);
        $this->MaintainVariable('IsLeapyear', 'Ist Schaltjahr?', vtBoolean, 'ALMANAC.Question', 4, $date);
        $this->MaintainVariable('IsWeekend', 'Ist Wochenende?', vtBoolean, 'ALMANAC.Question', 5, $date);
        $this->MaintainVariable('WeekNumber', 'Kalenderwoche', vtInteger, '', 30, $date);
        $this->MaintainVariable('DaysInMonth', 'Tage im Monat', vtInteger, '', 32, $date);
        $this->MaintainVariable('DayOfYear', 'Tag im Jahr', vtInteger, '', 33, $date);
        // Working Days (Arbeitstage im Monat)
        $this->MaintainVariable('WorkingDays', 'Arbeitstage im Monat', vtInteger, '', 40, $date);
        // Calculate next update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 1);
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_Update($id);
     */
    public function Update()
    {
        $holiday = $this->ReadPropertyBoolean('UpdateHoliday');
        $vacation = $this->ReadPropertyBoolean('UpdateVacation');
        $date = $this->ReadPropertyBoolean('UpdateDate');

        if ($holiday || $vacation || $date) {
            $data = $this->ExtractDates(time());
        }

        if ($holiday == true) {
            try {
                $this->SetValueString('Holiday', $data['Holiday']);
                $this->SetValueBoolean('IsHoliday', $data['IsHoliday']);
            } catch (Exception $exc) {
                trigger_error($exc->getMessage(), $exc->getCode());
                $this->SendDebug('ERROR HOLIDAY', $exc->getMessage(), 0);
            }
        }
        if ($vacation == true) {
            try {
                $this->SetValueString('Vacation', $data['SchoolHolidays']);
                $this->SetValueBoolean('IsVacation', $data['IsSchoolHolidays']);
            } catch (Exception $exc) {
                trigger_error($exc->getMessage(), $exc->getCode());
                $this->SendDebug('ERROR VACATION', $exc->getMessage(), 0);
            }
        }
        if ($date == true) {
            try {
                $this->SetValueBoolean('IsSummer', $date['IsSummer']);
                $this->SetValueBoolean('IsLeapyear', $date['IsLeapYear']);
                $this->SetValueBoolean('IsWeekend', $date['IsWeekend']);
                $this->SetValueInteger('WeekNumber', $date['WeekNumber']);
                $this->SetValueInteger('DaysInMonth', $date['DaysInMonth']);
                $this->SetValueInteger('DayOfYear', $date['DayOfYear']);
                $this->SetValueInteger('WorkingDays', $date['WorkingDays']);
            } catch (Exception $exc) {
                trigger_error($exc->getMessage(), $exc->getCode());
                $this->SendDebug('ERROR DATE', $exc->getMessage(), 0);
            }
        }

        // calculate next update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 1);
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_GetDateInfo($id, $ts);
     *
     * @param int    $ts Timestamp of the actuale date
     * @return array all extracted infomation about the passed date
     */
    public function GetDateInfo(int $ts): array
    {
        // Properties
        $state = $this->ReadPropertyString('State');
        $url = $this->ReadPropertyString('BaseURL');
        // Output array
        $date = [];

        // simple date infos
        $date['IsSummer'] = boolval(date('I', $ts));
        $date['IsLeapYear'] = boolval(date('L', $ts));
        $date['IsWeekend'] = boolval(date('N', $ts) > 5);
        $date['WeekNumber'] = idate('W', $ts);
        $date['DaysInMonth'] = idate('t', $ts);
        $date['DayOfYear'] = idate('z', $ts) + 1;

        // get holiday data
        $year = date('Y', $ts);
        $link = $url . '?land=' . $state . '&type=0&year=' . $year;
        $data = ExtractDates($link);

        // working days
        $fdm = date('Ym01', $ts);
        $ldm = date('Ymt', $ts);
        $nwd = 0;
        for ($day = $fdm; $day <= $ldm; $day++) {
            // Minus Weekends
            if (date('N', strtotime($day)) > 5) {
                $nwd++;
            }
            // Minus Holidays
            else {
                foreach ($data as $entry) {
                    if ($entry['start'] == $day) {
                        $nwd++;
                        break;
                    }
                }
            }
        }
        $date['WorkingDays'] = $date['DaysInMonth'] - $nwd;

        // check holiday
        $holiday = 'Kein Feiertag';
        $now = date('Ymd', $ts) . "\n";
        foreach ($data as $entry) {
            if (($now >= $entry['start']) && ($now <= $entry['end'])) {
                $holiday = $entry['name'];
                $this->SendDebug('FOUND', $holiday, 0);
                break;
            }
        }
        $date['Holiday'] = $holiday;
        $date['IsHoliday'] = ($holiday == 'Kein Feiertag') ? false : true;

        // check school holidays
        if ((int) date('md', $ts) < 110) {
            $year = date('Y', $ts) - 1;
            $link = $url . '?land=' . $state . '&type=1&year=' . $year;
            $data0 = ExtractDates($link);
        } else {
            $data0 = [];
        }
        $year = date('Y', $ts);
        $link = $url . '?land=' . $state . '&type=1&year=' . $year;
        $data1 = ExtractDates($link);
        $data = array_merge($data0, $data1);
        $vacation = 'Keine Ferien';
        foreach ($data as $entry) {
            if (($now >= $entry['start']) && ($now <= $entry['end'])) {
                $vacation = explode(' ', $entry['name'])[0];
                //$this->SendDebug('FOUND', $vacation, 0);
                break;
            }
        }
        $date['SchoolHolidays'] = $vacation;
        $date['IsSchoolHolidays'] = ($vacation == 'Keine Ferien') ? false : true;

        // return date info array
        return $date;
    }

    /**
     * Get and extract dates from iCal format.
     *
     * @param string $Ident Ident of the boolean variable
     * @param bool   $value Value of the boolean variable
     * @return array two-dimensional array, each date in one array
     * @throws Exception if calendar could not loaded.
     */
    private function ExtractDates(string $url): array
    {
        // $this->SendDebug('GET', $link, 0);
        // iCal URL als Array einlesen
        $ics = @file($url);
        // Fehlerbehandlung
        if ($ics === false) {
            throw new Exception('Cannot load iCal Data.', E_USER_NOTICE);
        }
        // Anzahl Zeilen
        $count = (count($ics) - 1);
        // daten
        $data = [];
        // loop through lines
        for ($line = 0; $line < $count; $line++) {
            if (strstr($ics[$line], 'SUMMARY:')) {
                $name = trim(substr($ics[$line], 8));
                $pos = strpos($name, '(Bankfeiertag)');
                if ($pos > 0) {
                    $name = substr($name, 0, $pos - 1);
                }
                $start = trim(substr($ics[$line + 1], 19));
                $end = trim(substr($ics[$line + 2], 17));
                $data[] = ['name' => $name, 'start' => $start, 'end' => $end];
            }
        }
        return $data;
    }

    /**
     * Update a boolean value.
     *
     * @param string $ident Ident of the boolean variable
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
     * @param string $ident Ident of the boolean variable
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
     * @param string $ident Ident of the boolean variable
     * @param int    $value Value of the string variable
     */
    private function SetValueInteger(string $ident, int $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueInteger($id, $value);
    }
}