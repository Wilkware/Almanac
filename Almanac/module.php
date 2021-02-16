<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/_traits.php';  // Generell funktions

// CLASS AlmanacModule
class AlmanacModule extends IPSModule
{
    use CalendarHelper;
    use ProfileHelper;
    use EventHelper;
    use DebugHelper;
    use WebhookHelper;

    /**
     * Supported Dates (BD = Birthdays, WD = Weddingdays, DD = Deathdays)
     */
    const BD = 'BD';
    const WD = 'WD';
    const DD = 'DD';

    /**
     * Date Properties (Form)
     */
    const DP = [
        self::BD => ['UpdateBirth', 'Birthdays', 'BirthdayNotification', 'BirthdayTime', 'BirthdayMessage', 'BirthdayDuration', 'BirthdayFormat'],
        self::WD => ['UpdateWedding', 'Weddingdays', 'WeddingdayNotification', 'WeddingdayTime', 'WeddingdayMessage', 'WeddingdayDuration', 'WeddingdayFormat'],
        self::DD => ['UpdateDeath', 'Deathdays', 'DeathdayNotification', 'DeathdayTime', 'DeathdayMessage', 'DeathdayDuration', 'DeathdayFormat'],
    ];

    /**
     * Create.
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // Public Holidays
        $this->RegisterPropertyString('PublicCountry', 'de');
        $this->RegisterPropertyString('PublicRegion', 'baden-wuerttemberg');
        $this->RegisterAttributeString('PublicURL', 'https://api.asmium.de/holiday/YEAR/COUNTRY/REGION/');
        // School Vacation
        $this->RegisterPropertyString('SchoolCountry', 'de');
        $this->RegisterPropertyString('SchoolRegion', 'baden-wuerttemberg');
        $this->RegisterPropertyString('SchoolName', 'alle-schulen');
        $this->RegisterAttributeString('SchoolURL', 'https://api.asmium.de/vacation/YEAR/COUNTRY/REGION/');
        // Birthdays
        $this->RegisterPropertyString('Birthdays', '');
        $this->RegisterPropertyInteger('BirthdayNotification', 0);
        $this->RegisterPropertyString('BirthdayTime', '{"hour":9,"minute":0,"second":0}');
        $this->RegisterPropertyInteger('BirthdayMessage', 0);
        $this->RegisterPropertyInteger('BirthdayDuration', 0);
        $this->RegisterPropertyString('BirthdayFormat', $this->Translate('%Y. birthday of %N (%E)'));
        // Wedding days
        $this->RegisterPropertyString('Weddingdays', '');
        $this->RegisterPropertyInteger('WeddingdayNotification', 0);
        $this->RegisterPropertyString('WeddingdayTime', '{"hour":9,"minute":0,"second":0}');
        $this->RegisterPropertyInteger('WeddingdayMessage', 0);
        $this->RegisterPropertyInteger('WeddingdayDuration', 0);
        $this->RegisterPropertyString('WeddingdayFormat', $this->Translate('%Y. wedding anniversary of %N (%E)'));
        // Death days
        $this->RegisterPropertyString('Deathdays', '');
        $this->RegisterPropertyInteger('DeathdayNotification', 0);
        $this->RegisterPropertyString('DeathdayTime', '{"hour":9,"minute":0,"second":0}');
        $this->RegisterPropertyInteger('DeathdayMessage', 0);
        $this->RegisterPropertyInteger('DeathdayDuration', 0);
        $this->RegisterPropertyString('DeathdayFormat', $this->Translate('%Y. anniversary of the death of %N (%E)'));
        // Advanced Settings
        $this->RegisterPropertyBoolean('UpdateHoliday', true);
        $this->RegisterPropertyBoolean('UpdateVacation', true);
        $this->RegisterPropertyBoolean('UpdateFestive', true);
        $this->RegisterPropertyBoolean('UpdateBirthday', true);
        $this->RegisterPropertyBoolean('UpdateWedding', true);
        $this->RegisterPropertyBoolean('UpdateDeath', true);
        $this->RegisterPropertyBoolean('UpdateDate', true);
        $this->RegisterPropertyInteger('InstanceWebfront', 0);
        $this->RegisterPropertyInteger('ScriptMessage', 0);
        // Register daily update timer
        $this->RegisterTimer('UpdateTimer', 0, 'ALMANAC_Update(' . $this->InstanceID . ');');
        // Register birth|wedding|death day notification timer
        $this->RegisterTimer('UpdateBirth', 0, 'ALMANAC_Notify(' . $this->InstanceID . ', "' . self::BD . '");');
        $this->RegisterTimer('UpdateWedding', 0, 'ALMANAC_Notify(' . $this->InstanceID . ', "' . self::WD . '");');
        $this->RegisterTimer('UpdateDeath', 0, 'ALMANAC_Notify(' . $this->InstanceID . ', "' . self::DD . '");');
    }

    /**
     * Destroy.
     */
    public function Destroy()
    {
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterHook('/hook/almanac' . $this->InstanceID);
        }
        parent::Destroy();
    }

    /**
     * Configuration Form.
     *
     * @return JSON configuration string.
     */
    public function GetConfigurationForm()
    {
        // read setup
        $publicCountry = $this->ReadPropertyString('PublicCountry');
        $publicHoliday = $this->ReadPropertyString('PublicRegion');
        // School Vacation
        $schoolCountry = $this->ReadPropertyString('SchoolCountry');
        $schoolRegion = $this->ReadPropertyString('SchoolRegion');
        $schoolName = $this->ReadPropertyString('SchoolName');
        // Debug output
        $this->SendDebug('GetConfigurationForm', 'public country=' . $publicCountry . ', public holiday=' . $publicHoliday .
                        ', school country=' . $schoolCountry . ', school vacation=' . $schoolRegion . ', school name=' . $schoolName, 0);
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // Holiday Regions
        $form['elements'][2]['items'][1]['options'] = $this->GetRegions($data[$publicCountry]);
        // Vacation Regions
        $form['elements'][3]['items'][1]['items'][0]['options'] = $this->GetRegions($data[$schoolCountry]);
        // Schools
        $form['elements'][3]['items'][1]['items'][1]['options'] = $this->GetSchool($data[$schoolCountry], $schoolRegion);
        // Debug output
        //$this->SendDebug('GetConfigurationForm', $form);
        return json_encode($form);
    }

    /**
     * Apply Configuration Changes.
     */
    public function ApplyChanges()
    {
        // Never delete this line!
        parent::ApplyChanges();
        // Public Holidays
        $publicCountry = $this->ReadPropertyString('PublicCountry');
        $publicRegion = $this->ReadPropertyString('PublicRegion');
        // School Vacation
        $schoolCountry = $this->ReadPropertyString('SchoolCountry');
        $schoolRegion = $this->ReadPropertyString('SchoolRegion');
        $schoolName = $this->ReadPropertyString('SchoolName');
        // Settings
        $isHoliday = $this->ReadPropertyBoolean('UpdateHoliday');
        $isVacation = $this->ReadPropertyBoolean('UpdateVacation');
        $isFestive = $this->ReadPropertyBoolean('UpdateFestive');
        $isDate = $this->ReadPropertyBoolean('UpdateDate');
        $this->SendDebug('ApplyChanges', 'public country=' . $publicCountry . ', public holiday=' . $publicRegion .
                        ', school country=' . $schoolCountry . ', school vacation=' . $schoolRegion . ', school name=' . $schoolName .
                        ', updates=' . ($isHoliday ? 'Y' : 'N') . '|' . ($isVacation ? 'Y' : 'N') . '|' . ($isFestive ? 'Y' : 'N') . '|' . ($isDate ? 'Y' : 'N'), 0);
        // Profile
        $association = [
            [0, 'Nein', 'Close', 0xFF0000],
            [1, 'Ja',   'Ok', 0x00FF00],
        ];
        $this->RegisterProfile(vtBoolean, 'ALMANAC.Question', 'Bulb', '', '', 0, 0, 0, 0, $association);
        // Webhook for exports
        $this->RegisterHook('/hook/almanac' . $this->InstanceID);
        // Holiday (Feiertage)
        $this->MaintainVariable('IsHoliday', 'Ist Feiertag?', vtBoolean, 'ALMANAC.Question', 1, $isHoliday);
        $this->MaintainVariable('Holiday', 'Feiertag', vtString, '', 10, $isHoliday);
        // Vacation (Schulferien)
        $this->MaintainVariable('IsVacation', 'Ist Ferienzeit?', vtBoolean, 'ALMANAC.Question', 2, $isVacation);
        $this->MaintainVariable('Vacation', 'Ferien', vtString, '', 20, $isVacation);
        // Festive (Festtage)
        $this->MaintainVariable('IsFestive', 'Ist Festtag?', vtBoolean, 'ALMANAC.Question', 6, $isFestive);
        $this->MaintainVariable('Festive', 'Festtag', vtString, '', 25, $isFestive);
        // Date
        $this->MaintainVariable('IsSummer', 'Ist Sommerzeit?', vtBoolean, 'ALMANAC.Question', 3, $isDate);
        $this->MaintainVariable('IsLeapyear', 'Ist Schaltjahr?', vtBoolean, 'ALMANAC.Question', 4, $isDate);
        $this->MaintainVariable('IsWeekend', 'Ist Wochenende?', vtBoolean, 'ALMANAC.Question', 5, $isDate);
        $this->MaintainVariable('WeekNumber', 'Kalenderwoche', vtInteger, '', 30, $isDate);
        $this->MaintainVariable('DaysInMonth', 'Tage im Monat', vtInteger, '', 32, $isDate);
        $this->MaintainVariable('DayOfYear', 'Tag im Jahr', vtInteger, '', 33, $isDate);
        // Working Days (Arbeitstage im Monat)
        $this->MaintainVariable('WorkingDays', 'Arbeitstage im Monat', vtInteger, '', 40, $isDate);
        // Season (Jahreszeit)
        $this->MaintainVariable('Season', 'Jahreszeit', vtString, '', 50, $isDate);
        // Calculate next date info update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 1);
        // Calculate next notification timer interval
        foreach (self::DP as $key => $value) {
            $data = json_decode($this->ReadPropertyString($value[3]), true);
            $this->UpdateTimerInterval($value[0], $data['hour'], $data['minute'], $data['second']);
        }
    }

    /**
     * RequestAction.
     *
     *  @param string $ident Ident.
     *  @param string $value Value.
     */
    public function RequestAction($ident, $value)
    {
        // Debug output
        $this->SendDebug('RequestAction', $ident . ' => ' . $value);
        // Ident == OnXxxxxYyyyy
        switch ($ident) {
            case 'OnPublicCountry':
                $this->OnPublicCountry($value);
            break;
            case 'OnSchoolCountry':
                $this->OnSchoolCountry($value);
            break;
            case 'OnSchoolRegion':
                $this->OnSchoolRegion($value);
            break;
            case 'OnImportBirthdays':
                $this->OnImportBirthdays($value);
            break;
            case 'OnImportWeddingdays':
                $this->OnImportWeddingdays($value);
            break;
            case 'OnImportDeathdays':
                $this->OnImportDeathdays($value);
            break;
            case 'OnDeleteDays':
                $this->OnDeleteDays($value);
            break;
        }
        // return true;
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_Update($id);
     */
    public function Notify(string $days)
    {
        $this->SendDebug('Notify', $days);
        // Notify enabled?
        $isDay = $this->ReadPropertyInteger(self::DP[$days][2]);
        // Webfront configured?
        $wfc = $this->ReadPropertyInteger('InstanceWebfront');
        // Lookup
        if ($isDay && ($wfc != 0)) {
            try {
                $this->LookupDays(self::DP[$days], $wfc, 0);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->SendDebug('ERROR NOTIFY', $ex->getMessage(), 0);
            }
        }
        // Calculate next notification timer interval
        $data = json_decode($this->ReadPropertyString(self::DP[$days][3]), true);
        $this->UpdateTimerInterval(self::DP[$days][0], $data['hour'], $data['minute'], $data['second']);
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_Update($id);
     */
    public function Update()
    {
        // General Date
        $isHoliday = $this->ReadPropertyBoolean('UpdateHoliday');
        $isVacation = $this->ReadPropertyBoolean('UpdateVacation');
        $isFestive = $this->ReadPropertyBoolean('UpdateFestive');
        $isDate = $this->ReadPropertyBoolean('UpdateDate');
        // B-W-D-Days
        $isBirth = $this->ReadPropertyInteger('BirthdayMessage');
        $isWedding = $this->ReadPropertyInteger('WeddingdayMessage');
        $isDeath = $this->ReadPropertyInteger('DeathdayMessage');
        // MessageScript
        $script = $this->ReadPropertyInteger('ScriptMessage');
        // Everything to do?
        if ($isHoliday || $isVacation || $isFestive || $isDate) {
            $date = json_decode($this->DateInfo(time()), true);
        }
        // Public Holidays
        if ($isHoliday == true) {
            try {
                $this->SetValueString('Holiday', $date['Holiday']);
                $this->SetValueBoolean('IsHoliday', $date['IsHoliday']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->SendDebug('ERROR HOLIDAY', $ex->getMessage(), 0);
            }
        }
        // School Vacations
        if ($isVacation == true) {
            try {
                $this->SetValueString('Vacation', $date['Vacation']);
                $this->SetValueBoolean('IsVacation', $date['IsVacation']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->SendDebug('ERROR VACATION', $ex->getMessage(), 0);
            }
        }
        // Festive Days
        if ($isFestive == true) {
            try {
                $this->SetValueString('Festive', $date['Festive']);
                $this->SetValueBoolean('IsFestive', $date['IsFestive']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->SendDebug('ERROR FESTIVE', $ex->getMessage(), 0);
            }
        }
        // General Date Info
        if ($isDate == true) {
            try {
                $this->SetValueBoolean('IsSummer', $date['IsSummer']);
                $this->SetValueBoolean('IsLeapyear', $date['IsLeapYear']);
                $this->SetValueBoolean('IsWeekend', $date['IsWeekend']);
                $this->SetValueInteger('WeekNumber', $date['WeekNumber']);
                $this->SetValueInteger('DaysInMonth', $date['DaysInMonth']);
                $this->SetValueInteger('DayOfYear', $date['DayOfYear']);
                $this->SetValueInteger('WorkingDays', $date['WorkingDays']);
                $this->SetValueString('Season', $date['Season']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->SendDebug('ERROR DATE', $ex->getMessage(), 0);
            }
        }
        // Day Infos to Dashboard?
        if ($script != 0) {
            if ($isBirth == true) {
                try {
                    $this->LookupDays(self::DP[self::BD], 0, $script);
                } catch (Exception $ex) {
                    $this->LogMessage($ex->getMessage(), KL_ERROR);
                    $this->SendDebug('ERROR BIRTH', $ex->getMessage(), 0);
                }
            }
            if ($isWedding == true) {
                try {
                    $this->LookupDays(self::DP[self::WD], 0, $script);
                } catch (Exception $ex) {
                    $this->LogMessage($ex->getMessage(), KL_ERROR);
                    $this->SendDebug('ERROR WEDDING', $ex->getMessage(), 0);
                }
            }
            if ($isDeath == true) {
                try {
                    $this->LookupDays(self::DP[self::DD], 0, $script);
                } catch (Exception $ex) {
                    $this->LogMessage($ex->getMessage(), KL_ERROR);
                    $this->SendDebug('ERROR DEATH', $ex->getMessage(), 0);
                }
            }
        }
        // calculate next update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 1);
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_DateInfo($id, $ts);
     *
     * @param int $ts Timestamp of the actuale date
     * @return string all extracted infomation about the passed date as json
     */
    public function DateInfo(int $ts): string
    {
        $this->SendDebug('DATE: ', date('d.m.Y', $ts));
        // Output array
        $date = [];

        // simple date infos
        $date['IsSummer'] = boolval(date('I', $ts));
        $date['IsLeapYear'] = boolval(date('L', $ts));
        $date['IsWeekend'] = boolval(date('N', $ts) > 5);
        $date['WeekNumber'] = idate('W', $ts);
        $date['DaysInMonth'] = idate('t', $ts);
        $date['DayOfYear'] = idate('z', $ts) + 1; // idate('z') is zero based

        // season info
        $date['Season'] = $this->Translate($this->Season($ts));

        // get festive days
        $isFestive = $this->LookupCalendar($ts);
        $date['Festive'] = $isFestive;
        $date['IsFestive'] = ($isFestive == 'Kein Festtag') ? false : true;

        // get holiday data
        $country = $this->ReadPropertyString('PublicCountry');
        $region = $this->ReadPropertyString('PublicRegion');
        $year = date('Y', $ts);
        $url = $this->ReadAttributeString('PublicURL');
        // prepeare API-URL
        $link = str_replace('COUNTRY', $country, $url);
        $link = str_replace('REGION', $region, $link);
        $link = str_replace('YEAR', $year, $link);
        $data = $this->ExtractDates($link);
        // working days
        $fdm = date('Ym01', $ts);
        $ldm = date('Ymt', $ts);
        $nwd = 0;
        for ($day = $fdm; $day <= $ldm; $day++) {
            // Minus Weekends
            if (date('N', strtotime(strval($day))) > 5) {
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
        $isHoliday = 'Kein Feiertag';
        $now = date('Ymd', $ts);
        foreach ($data as $entry) {
            if (($now >= $entry['start']) && ($now <= $entry['end'])) {
                $isHoliday = $entry['event'];
                $this->SendDebug('HOLIDAY: ', $isHoliday, 0);
                break;
            }
        }
        $date['Holiday'] = $isHoliday;
        $date['IsHoliday'] = ($isHoliday == 'Kein Feiertag') ? false : true;
        // no data, no info
        if (empty($data)) {
            $date['Holiday'] = 'Feiertag nicht ermittelbar';
            $date['IsHoliday'] = false;
        }
        // get vication data
        $country = $this->ReadPropertyString('SchoolCountry');
        $region = $this->ReadPropertyString('SchoolRegion');
        $school = $this->ReadPropertyString('SchoolName');
        $url = $this->ReadAttributeString('SchoolURL');
        // general replacement
        $url = str_replace('COUNTRY', $country, $url);
        if ($school != 'alle-schulen') {
            $region = $region . '_' . $school;
        }
        $url = str_replace('REGION', $region, $url);
        // check vacation
        if ((int) date('md', $ts) < 30) {
            $year = date('Y', $ts) - 1;
            $link = str_replace('YEAR', $year, $url);
            $data0 = $this->ExtractDates($link);
        } else {
            $data0 = [];
        }
        $year = date('Y', $ts);
        $link = str_replace('YEAR', $year, $url);
        $data1 = $this->ExtractDates($link);
        $data = array_merge($data0, $data1);
        $isVacation = 'Keine Ferien';
        foreach ($data as $entry) {
            if (($now >= $entry['start']) && ($now <= $entry['end'])) {
                $isVacation = explode(' ', $entry['event'])[0];
                $this->SendDebug('VACATION: ', $isVacation, 0);
                break;
            }
        }
        $date['Vacation'] = $isVacation;
        $date['IsVacation'] = ($isVacation == 'Keine Ferien') ? false : true;
        // no data, no info
        if (empty($data)) {
            $date['Vacation'] = 'Ferien nicht ermittelbar';
            $date['IsVacation'] = false;
        }
        // dump result
        $this->SendDebug('DATA: ', $date, 0);
        // return date info as json
        return json_encode($date);
    }

    /**
     * User has selected a new country.
     *
     * @param string $cid Country ID.
     */
    protected function OnPublicCountry($cid)
    {
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);
        // Region Options
        $this->UpdateFormField('PublicRegion', 'value', $data[$cid][0]['regions'][0]['ident']);
        $this->UpdateFormField('PublicRegion', 'options', json_encode($this->GetRegions($data[$cid])));
    }

    /**
     * User has selected a new country.
     *
     * @param string $cid Country ID.
     */
    protected function OnSchoolCountry($cid)
    {
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);
        // Region Options
        $region = $data[$cid][0]['regions'][0]['ident'];
        $this->SendDebug('DATA: ', $region, 0);
        $this->UpdateFormField('SchoolRegion', 'value', $region);
        $this->UpdateFormField('SchoolRegion', 'options', json_encode($this->GetRegions($data[$cid])));
        // School Options
        $this->UpdateFormField('SchoolName', 'value', $data[$cid][0]['regions'][0]['schools'][0]['ident']);
        $this->UpdateFormField('SchoolName', 'options', json_encode($this->GetSchool($data[$cid], $region)));
    }

    /**
     * User has selected a new school region.
     *
     * @param string $region region value.
     */
    protected function OnSchoolRegion($region)
    {
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

        // Sorry, find the country for the given region
        foreach ($data as $cid => $countries) {
            foreach ($countries[0]['regions'] as $rid => $regions) {
                if ($regions['ident'] == $region) {
                    // School Options
                    $this->UpdateFormField('SchoolName', 'value', $data[$cid][0]['regions'][$rid]['schools'][0]['ident']);
                    $this->UpdateFormField('SchoolName', 'options', json_encode($this->GetSchool($data[$cid], $region)));
                }
            }
        }
    }

    /**
     * Import birthdays data.
     *
     * @param string $value Base64 coded data.
     */
    protected function OnImportBirthdays($value)
    {
        $this->ImportCSV('Birthdays', $value);
    }

    /**
     * Import wedding days data.
     *
     * @param string $value Base64 coded data.
     */
    protected function OnImportWeddingdays($value)
    {
        $this->ImportCSV('Weddingdays', $value);
    }

    /**
     * Import death days data.
     *
     * @param string $value Base64 coded data.
     */
    protected function OnImportDeathdays($value)
    {
        $this->ImportCSV('Deathdays', $value);
    }

    /**
     * Clear the selected days list.
     *
     * @param string $value property shor name.
     */
    protected function OnDeleteDays($value)
    {
        $this->SendDebug('OnDeleteDays', $value);
        // with days
        $property = self::DP[$value][1];
        $data = [];
        $this->UpdateFormField($property, 'values', json_encode($data));
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     */
    protected function ProcessHookData()
    {
        //$this->SendDebug('ProcessHookData', $_GET);
        $export = isset($_GET['export']) ? $_GET['export'] : '';
        //$this->SendDebug('ProcessHookData', 'Export: ' . $export);
        $property = '';
        $filename = '';
        switch ($export) {
            case 'BD':
                $property = 'Birthdays';
                $filename = $this->Translate('birthdays.csv');
                break;
            case 'WD':
                $property = 'Weddingdays';
                $filename = $this->Translate('weddingdays.csv');
                break;
            case 'DD':
                $property = 'Deathdays';
                $filename = $this->Translate('deathdays.csv');
                break;
        default:
                return;
        }
        // get the current entries
        $this->SendDebug('ProcessHookData', $this->ReadPropertyString($property));
        $list = json_decode($this->ReadPropertyString($property), true);
        if (empty($list) || !is_array($list)) {
            $list = [];
        }
        // build value list
        $entry = [];
        foreach ($list as $key => $item) {
            if (is_array($item)) {
                $dt = json_decode($item['Date'], true);
                $bd = $dt['day'] . '.' . $dt['month'] . '.' . $dt['year'];
                $entry[] = [$bd, $item['Name']];
            }
        }
        // output headers so that the file is downloaded rather than displayed
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        // create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');
        // output line by line
        foreach ($entry as $fields) {
            fputcsv($output, $fields);
        }
    }

    /**
     * Lookup the calendar data to find a feast day.
     *
     * @param int $ts Date timestamp
     * @return string Name of a feast day for a given timestamp.
     */
    private function LookupCalendar(int $ts): string
    {
        // get generic calendar dates
        $calendar = json_decode(file_get_contents(__DIR__ . '/calendar.json'), true);
        // build year based dates
        $year = date('Y', $ts);
        $dates = [];
        foreach ($calendar['dates'] as $date) {
            $text = '';
            switch ($date['variant']) {
                case 0:
                    $text = $this->DateOf($year, $date['month'], $date['day']);
                    break;
                case 1:
                    $text = $this->DateWithReference($year, $date['day'], $date['offset'], $date['weekday']);
                    break;
                case 2:
                    $text = $this->DateToEaster($year, $date['offset']);
                    break;
                case 3:
                    $text = $this->DateForSeason($year, $date['month'], $date['day'], $date['shift']);
                    break;
                default:
                    $text = 'ERROR:';
            }
            $dates[$text] = $date['name'];
            //$this->SendDebug('Lookup', $text.' - '.$date['name']);
        }
        // lookup for given date
        $day = date('Ymd', $ts);
        if (array_key_exists($day, $dates)) {
            return $dates[$day];
        }
        return 'Kein Festtag';
    }

    /**
     * Lookup for Birth-, Wedding, Death-Days
     */
    private function LookupDays(array $property, int $visu = 0, int $script = 0)
    {
        // Debug output
        $this->SendDebug('LookupDays ', 'WFC: ' . $visu . ', MSG: ' . $script, 0);
        // 1 = 'Deathdays', 5 = 'DeathdayDuration', 6 = 'DeathdayFormat'
        $ts = time();
        $year = date('Y', $ts);
        $day = date('j', $ts);
        $mon = date('n', $ts);
        // get the current entries
        $list = json_decode($this->ReadPropertyString($property[1]), true);
        if (empty($list) || !is_array($list)) {
            $list = [];
        }
        // build value list
        $entry = [];
        foreach ($list as $key => $item) {
            if (is_array($item)) {
                $dt = json_decode($item['Date'], true);
                if ($day == $dt['day'] && $mon == $dt['month']) {
                    $bd = $dt['day'] . '.' . $dt['month'] . '.';
                    $entry[] = [$bd . $dt['year'], $bd . $year, $year - $dt['year'], $item['Name']];
                }
            }
        }
        // get format
        $format = $this->ReadPropertyString($property[6]);
        foreach ($entry as $item) {
            $output = str_replace('%E', $item[0], $format);
            $output = str_replace('%D', $item[1], $output);
            $output = str_replace('%Y', $item[2], $output);
            $output = str_replace('%N', $item[3], $output);
            if ($visu != 0) {
                WFC_PushNotification($visu, 'ALMANAC', $output, 'Calendar', 0);
            }
            if ($script != 0) {
                $time = $this->ReadPropertyInteger($property[5]);
                if ($time > 0) {
                    $msg = IPS_RunScriptWaitEx($script, ['action' => 'add', 'text' => $output, 'expires' => time() + $time, 'removable' => true, 'type' => 4, 'image' => 'Calendar']);
                } else {
                    $msg = IPS_RunScriptWaitEx($script, ['action' => 'add', 'text' => $output, 'removable' => true, 'type' => 4, 'image' => 'Calendar']);
                }
            }
        }
    }

    /**
     * Get and extract dates from iCal format.
     *
     * @param string $property Name of the list element
     * @param string $value Data to import (base64 coded)
     */
    private function ImportCSV(string $property, string $value)
    {
        $csv = base64_decode($value);
        $lines = preg_split('/[\r\n]{1,2}(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/', $csv);
        $data = [];
        foreach ($lines as $row) {
            $data[] = str_getcsv($row);
        }
        // check ... was comma
        $cols = max(array_map('count', $data));
        if($cols != 2) {
            unset($data);
            foreach ($lines as $row) {
                $data[] = str_getcsv($row, ';');
            }
        }
        // check ... was semicolon
        $cols = max(array_map('count', $data));
        if($cols != 2) {
            $this->SendDebug('ImportCSV', 'No CSV format found!');
            return;
        }
        // get the current entries
        //$this->SendDebug("I1", $this->ReadPropertyString('Birthdays'));
        $list = json_decode($this->ReadPropertyString($property), true);
        if (empty($list) || !is_array($list)) {
            $list = [];
        }
        // build value list
        $entry = [];
        foreach ($data as $key => $item) {
            if (is_array($item) && isset($item[0])) {
                $dt = date_parse($item[0]);
                $bd = '{"year":' . $dt['year'] . ',"month":' . $dt['month'] . ',"day":' . $dt['day'] . '}';
                $entry[] = ['Date' => $bd, 'Name' => $item[1]];
            }
        }
        // merge both
        $data = array_merge($list, $entry);
        // remve multi dimension
        $data = array_map('serialize', $data);
        // remove duplicates
        $data = array_unique($data);
        // back to multidimension array
        $data = array_map('unserialize', $data);
        // remove index key
        $data = array_values($data);
        // Update list values
        //$this->SendDebug("I2", json_encode($data));
        $this->UpdateFormField($property, 'values', json_encode($data));
    }

    /**
     * Get and extract dates from json format.
     *
     * @param string $url API URL to receive event information.
     * @return array  array, with name, start and end date
     */
    private function ExtractDates(string $url): array
    {
        // Debug output
        $this->SendDebug('LINK: ', $url, 0);
        // read API URL
        $json = @file_get_contents($url);
        // error handling
        if ($json === false) {
            $this->LogMessage($this->Translate('Could not load json data!'), KL_ERROR);
            $this->SendDebug('ExtractDates', 'ERROR LOAD DATA', 0);
            return [];
        }
        // json decode
        $data = json_decode($json, true);
        // return the events
        return $data['data']['events'];
    }

    /**
     * Reads the public regions for a given country.
     *
     * @param string $country country data array.
     * @return array Region options array.
     */
    private function GetRegions(array $country): array
    {
        $options = [];
        // Client List
        foreach ($country[0]['regions'] as $rid => $regions) {
            $options[] = ['caption' => $regions['name'], 'value'=> $regions['ident']];
        }
        return $options;
    }

    /**
     * Reads the schools for a given region.
     *
     * @param string $country country data array.
     * @param string $region region ident.
     * @return array School options array.
     */
    private function GetSchool(array $country, string $region): array
    {
        $options = [];
        // Client List
        foreach ($country[0]['regions'] as $rid => $regions) {
            if ($regions['ident'] == $region) {
                foreach ($regions['schools'] as $sid => $schools) {
                    $options[] = ['caption' => $schools['name'], 'value'=> $schools['ident']];
                }
                break;
            }
        }
        return $options;
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
     * @param string $ident Ident of the string variable
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
     * @param string $ident Ident of the integer variable
     * @param int    $value Value of the integer variable
     */
    private function SetValueInteger(string $ident, int $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueInteger($id, $value);
    }
}