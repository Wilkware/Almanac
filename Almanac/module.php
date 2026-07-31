<?php

declare(strict_types=1);

/** Generell funktions  */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\Almanac\CacheHelper;
use Wilkware\Almanac\CalendarHelper;
use Wilkware\Almanac\DebugHelper;
use Wilkware\Almanac\EventHelper;
use Wilkware\Almanac\VariableHelper;
use Wilkware\Almanac\VersionHelper;

/**
 * CLASS Almanac
 */
class Almanac extends IPSModuleStrict
{
    // -------------------------------------------------------------------------
    // Traits
    // -------------------------------------------------------------------------

    use CacheHelper;
    use CalendarHelper;
    use DebugHelper;
    use EventHelper;
    use VariableHelper;
    use VersionHelper;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** @var string Supported Dates (BD = Birthdays) */
    private const ALMANAC_BD = 'BD';
    /** @var string Supported Dates (WD = Weddingdays) */
    private const ALMANAC_WD = 'WD';
    /** @var string Supported Dates (DD = Deathdays) */
    private const ALMANAC_DD = 'DD';

    /** @var array<string,array<string>> Date Properties (Form) */
    private const ALMANAC_DP = [
        self::ALMANAC_BD => ['UpdateBirth', 'Birthdays', 'BirthdayNotification', 'BirthdayTime', 'BirthdayMessage', 'BirthdayDuration', 'BirthdayFormat', 'BirthdayVariable', 'BirthdaySeparator', 'NoBirthday'],
        self::ALMANAC_WD => ['UpdateWedding', 'Weddingdays', 'WeddingdayNotification', 'WeddingdayTime', 'WeddingdayMessage', 'WeddingdayDuration', 'WeddingdayFormat', 'WeddingdayVariable', 'WeddingdaySeparator', 'NoWedding'],
        self::ALMANAC_DD => ['UpdateDeath', 'Deathdays', 'DeathdayNotification', 'DeathdayTime', 'DeathdayMessage', 'DeathdayDuration', 'DeathdayFormat', 'DeathdayVariable', 'DeathdaySeparator', 'NoDeath'],
    ];

    /** @var int Cache time dor a day */
    private const ALMANAC_SECONDS_PER_DAY = 86400;

    /**
     * @var array<string,int>
     * Cache timeouts per URL pattern (seconds)
     * 0 = load once, keep forever (until IPS restart or ClearCache())
     */
    private const ALMANAC_CACHE_RULES = [
        'holiday'   => 90 * self::ALMANAC_SECONDS_PER_DAY,  // 90 days
        'vacation'  => 30 * self::ALMANAC_SECONDS_PER_DAY,  // 30 days
        'astronomy' => 180 * self::ALMANAC_SECONDS_PER_DAY, // 180 days
        'quotes'    => 0                            // load once, keep forever
    ];

    /** @var string Prefix for custom functions which should be available from outside */
    private const ALMANAC_PREFIX_HOOK = 'almanac';

    // -------------------------------------------------------------------------
    // Presentations
    // -------------------------------------------------------------------------

    /**
     * @var array<string,mixed> Question Presentation (Value)
     */
    private const ALMANAC_PRESENTATION_QUESTION = [
        'USAGE_TYPE'          => 0,
        'THOUSANDS_SEPARATOR' => '',
        'SHOW_PREVIEW'        => true,
        'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
        'SUFFIX'              => '',
        'COLOR'               => -1,
        'PREFIX'              => '',
        'CONTENT_COLOR'       => -1,
        'MAX'                 => 100,
        'MULTILINE'           => false,
        'DECIMAL_SEPARATOR'   => 'Client',
        'PERCENTAGE'          => false,
        'DIGITS'              => 2,
        'INTERVALS'           => '[]',
        'DISPLAY_TYPE'        => 0,
        'ICON'                => 'question',
        'INTERVALS_ACTIVE'    => false,
        'PREVIEW_STYLE'       => 1,
        'MIN'                 => 0,
        'OPTIONS'             => '[{"ColorDisplay":16711680,"ContentColorDisplay":-1,"Value":false,"Caption":"No","IconActive":true,"IconValue":"calendar-xmark","ColorActive":true,"ColorValue":16711680,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":65280,"ContentColorDisplay":-1,"Value":true,"Caption":"Yes","IconActive":true,"IconValue":"calendar-check","ColorActive":true,"ColorValue":65280,"ContentColorActive":false,"ContentColorValue":-1}]',
    ];

    /**
     * @var array<string,mixed> 4 Season Presentation (Value)
     */
    private const ALMANAC_PRESENTATION_SEASON = [
        'USAGE_TYPE'          => 0,
        'THOUSANDS_SEPARATOR' => '',
        'SHOW_PREVIEW'        => true,
        'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
        'SUFFIX'              => '',
        'COLOR'               => -1,
        'PREFIX'              => '',
        'CONTENT_COLOR'       => -1,
        'MAX'                 => 100,
        'MULTILINE'           => false,
        'DECIMAL_SEPARATOR'   => 'Client',
        'PERCENTAGE'          => false,
        'DIGITS'              => 2,
        'INTERVALS'           => '[]',
        'DISPLAY_TYPE'        => 0,
        'ICON'                => 'leaf',
        'INTERVALS_ACTIVE'    => false,
        'PREVIEW_STYLE'       => 1,
        'MIN'                 => 0,
        'OPTIONS'             => '[{"ColorDisplay":9225790,"ContentColorDisplay":-1,"Value":"Spring","Caption":"Spring","IconActive":false,"IconValue":"","ColorActive":true,"ColorValue":9225790,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":16635137,"ContentColorDisplay":-1,"Value":"Summer","Caption":"Summer","IconActive":false,"IconValue":"","ColorActive":true,"ColorValue":16635137,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":14249729,"ContentColorDisplay":-1,"Value":"Fall","Caption":"Fall","IconActive":false,"IconValue":"","ColorActive":true,"ColorValue":14249729,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":6670288,"ContentColorDisplay":-1,"Value":"Winter","Caption":"Winter","IconActive":false,"IconValue":"","ColorActive":true,"ColorValue":6670288,"ContentColorActive":false,"ContentColorValue":-1}]',
    ];

    /**
     * @var array<string,mixed> Wekday Presentation (Value)
     */
    private const ALMANAC_PRESENTATION_WEEKDAY = [
        'USAGE_TYPE'          => 0,
        'THOUSANDS_SEPARATOR' => '',
        'SHOW_PREVIEW'        => true,
        'PRESENTATION'        => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
        'SUFFIX'              => '',
        'COLOR'               => -1,
        'MAX'                 => 0,
        'MULTILINE'           => false,
        'DECIMAL_SEPARATOR'   => 'Client',
        'PERCENTAGE'          => false,
        'DIGITS'              => 0,
        'INTERVALS'           => '[{"ColorDisplay":8454016,"ContentColorDisplay":-1,"IntervalMinValue":1,"IntervalMaxValue":1,"ConstantActive":true,"ConstantValue":"Monday","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":8454016,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":8454016,"ContentColorDisplay":-1,"IntervalMinValue":2,"IntervalMaxValue":2,"ConstantActive":true,"ConstantValue":"Tuesday","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":8454016,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":8454016,"ContentColorDisplay":-1,"IntervalMinValue":3,"IntervalMaxValue":3,"ConstantActive":true,"ConstantValue":"Wednesday","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":8454016,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":8454016,"ContentColorDisplay":-1,"IntervalMinValue":4,"IntervalMaxValue":4,"ConstantActive":true,"ConstantValue":"Thursday","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":8454016,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":8454016,"ContentColorDisplay":-1,"IntervalMinValue":5,"IntervalMaxValue":5,"ConstantActive":true,"ConstantValue":"Friday","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":8454016,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":16777088,"ContentColorDisplay":-1,"IntervalMinValue":6,"IntervalMaxValue":6,"ConstantActive":true,"ConstantValue":"Saturday","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":16777088,"ContentColorActive":false,"ContentColorValue":-1},{"ColorDisplay":16744576,"ContentColorDisplay":-1,"IntervalMinValue":7,"IntervalMaxValue":7,"ConstantActive":true,"ConstantValue":"Sunday","ConversionFactor":1,"IconActive":false,"IconValue":"","PrefixActive":false,"PrefixValue":"","SuffixActive":false,"SuffixValue":"","DigitsActive":false,"DigitsValue":0,"ColorActive":true,"ColorValue":16744576,"ContentColorActive":false,"ContentColorValue":-1}]',
        'DISPLAY_TYPE'        => 0,
        'ICON'                => 'Calendar',
        'INTERVALS_ACTIVE'    => true,
        'PREVIEW_STYLE'       => 1,
        'MIN'                 => 0,
        'CONTENT_COLOR'       => -1,
        'PREFIX'              => '',
    ];

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
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
        $this->RegisterPropertyString('Birthdays', '[]');
        $this->RegisterPropertyInteger('BirthdayNotification', 0);
        $this->RegisterPropertyString('BirthdayTime', '{"hour":9,"minute":0,"second":0}');
        $this->RegisterPropertyInteger('BirthdayMessage', 0);
        $this->RegisterPropertyInteger('BirthdayDuration', 0);
        $this->RegisterPropertyString('BirthdayFormat', $this->Translate('%Y. birthday of %N (%E)'));
        $this->RegisterPropertyInteger('BirthdayVariable', 0);
        $this->RegisterPropertyString('BirthdaySeparator', ', ');
        // Wedding days
        $this->RegisterPropertyString('Weddingdays', '[]');
        $this->RegisterPropertyInteger('WeddingdayNotification', 0);
        $this->RegisterPropertyString('WeddingdayTime', '{"hour":9,"minute":0,"second":0}');
        $this->RegisterPropertyInteger('WeddingdayMessage', 0);
        $this->RegisterPropertyInteger('WeddingdayDuration', 0);
        $this->RegisterPropertyString('WeddingdayFormat', $this->Translate('%Y. wedding anniversary of %N (%E)'));
        $this->RegisterPropertyInteger('WeddingdayVariable', 0);
        $this->RegisterPropertyString('WeddingdaySeparator', ', ');
        // Death days
        $this->RegisterPropertyString('Deathdays', '[]');
        $this->RegisterPropertyInteger('DeathdayNotification', 0);
        $this->RegisterPropertyString('DeathdayTime', '{"hour":9,"minute":0,"second":0}');
        $this->RegisterPropertyInteger('DeathdayMessage', 0);
        $this->RegisterPropertyInteger('DeathdayDuration', 0);
        $this->RegisterPropertyString('DeathdayFormat', $this->Translate('%Y. anniversary of the death of %N (%E)'));
        $this->RegisterPropertyInteger('DeathdayVariable', 0);
        $this->RegisterPropertyString('DeathdaySeparator', ', ');
        // Various
        $this->RegisterPropertyString('EclipseFormat', $this->Translate('Next %N is on %D at %T o\'clock'));
        $this->RegisterPropertyString('MoonphaseFormat', $this->Translate('Next %N on %D at %T o\'clock'));
        $this->RegisterAttributeString('AstroURL', 'https://api.asmium.de/astronomy/YEAR/COUNTRY/EVENT/');
        $this->RegisterPropertyString('QuoteFormat', $this->Translate('„%Q“ - %A'));
        $this->RegisterAttributeString('QuoteURL', 'https://api.asmium.de/quotes/de/');
        $this->RegisterPropertyString('DateFormat', '%l, %j.%F');
        // Visualization
        $this->RegisterPropertyInteger('Desktop', 6);
        $this->RegisterPropertyInteger('Tablet', 4);
        $this->RegisterPropertyInteger('Mobile', 2);
        $this->RegisterPropertyString('Slots', '[]');
        // Advanced Settings
        $this->RegisterPropertyBoolean('UpdateHoliday', true);
        $this->RegisterPropertyBoolean('UpdateVacation', true);
        $this->RegisterPropertyBoolean('UpdateFestive', true);
        $this->RegisterPropertyBoolean('UpdateBirthday', true);
        $this->RegisterPropertyBoolean('UpdateWedding', true);
        $this->RegisterPropertyBoolean('UpdateDeath', true);
        $this->RegisterPropertyBoolean('UpdateEclipse', true);
        $this->RegisterPropertyBoolean('UpdateMoonphase', true);
        $this->RegisterPropertyBoolean('UpdateQuote', true);
        $this->RegisterPropertyBoolean('UpdateDate', true);
        $this->RegisterPropertyBoolean('SchoolPeriod', false);
        $this->RegisterPropertyString('NoHoliday', $this->Translate('No public holiday'));
        $this->RegisterPropertyString('NoVacation', $this->Translate('No school vacation'));
        $this->RegisterPropertyString('NoFestive', $this->Translate('No festive day'));
        $this->RegisterPropertyString('NoBirthday', $this->Translate('No birthday'));
        $this->RegisterPropertyString('NoWedding', $this->Translate('No wedding day'));
        $this->RegisterPropertyString('NoDeath', $this->Translate('No deathday'));
        $this->RegisterPropertyInteger('InstanceWebfront', 0);
        $this->RegisterPropertyInteger('ScriptMessage', 0);
        // Register daily update timer
        $this->RegisterTimer('UpdateTimer', 0, 'ALMANAC_Update(' . $this->InstanceID . ');');
        // Register birth|wedding|death day notification timer
        $this->RegisterTimer('UpdateBirth', 0, 'ALMANAC_Notify(' . $this->InstanceID . ', "' . self::ALMANAC_BD . '");');
        $this->RegisterTimer('UpdateWedding', 0, 'ALMANAC_Notify(' . $this->InstanceID . ', "' . self::ALMANAC_WD . '");');
        $this->RegisterTimer('UpdateDeath', 0, 'ALMANAC_Notify(' . $this->InstanceID . ', "' . self::ALMANAC_DD . '");');

        // Set visualization type to 1, as we want to offer HTML
        $this->SetVisualizationType(1);
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting IP-Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        parent::Destroy();
    }

    /**
     * The content can be overwritten in order to transfer a self-created configuration page.
     * This way, content can be generated dynamically.
     * In this case, the "form.json" on the file system is completely ignored.
     *
     * @return string Content of the configuration page.
     */
    public function GetConfigurationForm(): string
    {
        // read setup
        $publicCountry = $this->ReadPropertyString('PublicCountry');
        $publicHoliday = $this->ReadPropertyString('PublicRegion');
        // School Vacation
        $schoolCountry = $this->ReadPropertyString('SchoolCountry');
        $schoolRegion = $this->ReadPropertyString('SchoolRegion');
        $schoolName = $this->ReadPropertyString('SchoolName');
        // Debug output
        $this->LogDebug('GetConfigurationForm', 'public country=' . $publicCountry . ', public holiday=' . $publicHoliday .
                        ', school country=' . $schoolCountry . ', school vacation=' . $schoolRegion . ', school name=' . $schoolName);
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // Holiday Regions
        $form['elements'][1]['items'][1]['options'] = $this->GetRegions($data[$publicCountry]);
        // Vacation Regions
        $form['elements'][2]['items'][1]['items'][0]['options'] = $this->GetRegions($data[$schoolCountry]);
        // Schools
        $form['elements'][2]['items'][1]['items'][1]['options'] = $this->GetSchool($data[$schoolCountry], $schoolRegion);
        // Extract Version
        $ins = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($ins['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);
        $form['actions'][2]['items'][2]['caption'] = sprintf('v%s.%d', $lib['Version'], $lib['Build']);
        // Debug output
        //$this->LogDebug(__FUNCTION__, $form);
        return json_encode($form);
    }

    /**
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
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
        $isBirthday = $this->ReadPropertyBoolean('UpdateBirthday');
        $isWeddingday = $this->ReadPropertyBoolean('UpdateWedding');
        $isDeathday = $this->ReadPropertyBoolean('UpdateDeath');
        $isEclipse = $this->ReadPropertyBoolean('UpdateEclipse');
        $isMoonphase = $this->ReadPropertyBoolean('UpdateMoonphase');
        $isQuote = $this->ReadPropertyBoolean('UpdateQuote');
        $isDate = $this->ReadPropertyBoolean('UpdateDate');
        // Birthday, Weddingday, Deathday needs variable?
        $isBirthday = $isBirthday && boolval($this->ReadPropertyInteger('BirthdayVariable'));
        $isWeddingday = $isWeddingday && boolval($this->ReadPropertyInteger('WeddingdayVariable'));
        $isDeathday = $isDeathday && boolval($this->ReadPropertyInteger('DeathdayVariable'));
        // Debug
        $this->LogDebug(__FUNCTION__, 'public country=' . $publicCountry . ', public holiday=' . $publicRegion .
                        ', school country=' . $schoolCountry . ', school vacation=' . $schoolRegion . ', school name=' . $schoolName .
                        ', updates=' . ($isHoliday ? 'Y' : 'N') . '|' . ($isVacation ? 'Y' : 'N') . '|' . ($isFestive ? 'Y' : 'N') . '|' . ($isEclipse ? 'Y' : 'N') . '|' . ($isMoonphase ? 'Y' : 'N') . '|' . ($isQuote ? 'Y' : 'N') . '|' . ($isDate ? 'Y' : 'N'));
        // Presentations
        $question = $this->TranslatePresentation(self::ALMANAC_PRESENTATION_QUESTION, 'OPTIONS', 'Caption');
        $weekday = $this->TranslatePresentation(self::ALMANAC_PRESENTATION_WEEKDAY, 'INTERVALS', 'ConstantValue');
        $season = $this->TranslatePresentation(self::ALMANAC_PRESENTATION_SEASON, 'OPTIONS', 'Value');
        // Webhook for exports
        $this->RegisterHook(self::ALMANAC_PREFIX_HOOK . $this->InstanceID);
        // Holiday (Feiertage)
        $this->MaintainVariable('IsHoliday', $this->Translate('Is holiday?'), VARIABLETYPE_BOOLEAN, $question, 101, $isHoliday);
        $this->MaintainVariable('Holiday', $this->Translate('Holiday'), VARIABLETYPE_STRING, '', 201, $isHoliday);
        // Vacation (Schulferien)
        $this->MaintainVariable('IsVacation', $this->Translate('Is vacation?'), VARIABLETYPE_BOOLEAN, $question, 102, $isVacation);
        $this->MaintainVariable('Vacation', $this->Translate('Vacation'), VARIABLETYPE_STRING, '', 202, $isVacation);
        // Festive (Festtage)
        $this->MaintainVariable('IsFestive', $this->Translate('Is festive day?'), VARIABLETYPE_BOOLEAN, $question, 103, $isFestive);
        $this->MaintainVariable('Festive', $this->Translate('Festive day'), VARIABLETYPE_STRING, '', 203, $isFestive);
        // Birthday (Geburtstage)
        $this->MaintainVariable('IsBirthday', $this->Translate('Is birthday?'), VARIABLETYPE_BOOLEAN, $question, 104, $isBirthday);
        $this->MaintainVariable('Birthday', $this->Translate('Birthday'), VARIABLETYPE_STRING, '', 204, $isBirthday);
        // Weddingday (Hochzeitstage)
        $this->MaintainVariable('IsWeddingday', $this->Translate('Is wedding day?'), VARIABLETYPE_BOOLEAN, $question, 105, $isWeddingday);
        $this->MaintainVariable('Weddingday', $this->Translate('Wedding day'), VARIABLETYPE_STRING, '', 205, $isWeddingday);
        // Deathday (Todestage)
        $this->MaintainVariable('IsDeathday', $this->Translate('Is death day?'), VARIABLETYPE_BOOLEAN, $question, 106, $isDeathday);
        $this->MaintainVariable('Deathday', $this->Translate('Death day'), VARIABLETYPE_STRING, '', 206, $isDeathday);
        // Eclipse (Mond- und Sonnnenfisternis)
        $this->MaintainVariable('IsEclipse', $this->Translate('Is lunar or solar eclipse?'), VARIABLETYPE_BOOLEAN, $question, 107, $isEclipse);
        $this->MaintainVariable('Eclipse', $this->Translate('Lunar or solar eclipse'), VARIABLETYPE_STRING, '', 207, $isEclipse);
        // Moonphase (Mondphasen)
        $this->MaintainVariable('IsMoonphase', $this->Translate('Is moon phase?'), VARIABLETYPE_BOOLEAN, $question, 108, $isMoonphase);
        $this->MaintainVariable('Moonphase', $this->Translate('Moon phase'), VARIABLETYPE_STRING, '', 208, $isMoonphase);
        // Quote of the day (Zitat des Tages)
        $this->MaintainVariable('QuoteOfTheDay', $this->Translate('Quote of the day'), VARIABLETYPE_STRING, '', 600, $isQuote);
        // Date (Tagesdaten)
        $this->MaintainVariable('IsSummer', $this->Translate('Is summer time?'), VARIABLETYPE_BOOLEAN, $question, 151, $isDate);
        $this->MaintainVariable('IsLeapyear', $this->Translate('Is leap year?'), VARIABLETYPE_BOOLEAN, $question, 152, $isDate);
        $this->MaintainVariable('IsWeekend', $this->Translate('Is weekend?'), VARIABLETYPE_BOOLEAN, $question, 153, $isDate);
        $this->MaintainVariable('WeekDay', $this->Translate('Weekday'), VARIABLETYPE_INTEGER, $weekday, 300, $isDate);
        $this->MaintainVariable('WeekNumber', $this->Translate('Week number'), VARIABLETYPE_INTEGER, '', 301, $isDate);
        $this->MaintainVariable('DaysInMonth', $this->Translate('Days in month'), VARIABLETYPE_INTEGER, '', 302, $isDate);
        $this->MaintainVariable('DayOfYear', $this->Translate('Day of year'), VARIABLETYPE_INTEGER, '', 303, $isDate);
        $this->MaintainVariable('DayLong', $this->Translate('Day format'), VARIABLETYPE_STRING, '', 304, $isDate);
        // Working Days (Arbeitstage im Monat)
        $this->MaintainVariable('WorkingDays', $this->Translate('Working days'), VARIABLETYPE_INTEGER, '', 400, $isDate);
        // Season (Jahreszeit)
        $this->MaintainVariable('Season', $this->Translate('Season'), VARIABLETYPE_STRING, $season, 500, $isDate);
        // Calculate next date info update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 30);
        // Calculate next notification timer interval
        foreach (self::ALMANAC_DP as $key => $value) {
            $data = json_decode($this->ReadPropertyString($value[3]), true);
            $this->UpdateTimerInterval($value[0], $data['hour'], $data['minute'], $data['second']);
        }
        // Update visualization
        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . $value);
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
            case 'CacheClear':
                $this->ClearCache('UrlCache', $value);
                break;
            case 'CacheInfo':
                $this->LogDebug(__FUNCTION__, $this->GetCacheInfo('UrlCache'));
                break;
        }
    }

    /**
     * If the HTML-SDK is to be used, this function must be overwritten in order to return the HTML content.
     *
     * @return string Initial display of a representation via HTML SDK
     */
    public function GetVisualizationTile(): string
    {
        // Add a script to set the values when loading, analogous to changes at runtime
        // Although the return from GetFullUpdateMessage is already JSON-encoded, json_encode is still executed a second time
        // This adds quotation marks to the string and any quotation marks within it are escaped correctly
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ');</script>';
        // Add static HTML from file
        $module = file_get_contents(__DIR__ . '/module.html');
        // Important: $initialHandling at the end, as the handleMessage function is only defined in the HTML
        return $module . $initialHandling;
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_Notify($id, $days);
     *
     * @param string $days Name of the notification day (Birthday etc.)
     * @return void
     */
    public function Notify(string $days): void
    {
        $this->LogDebug(__FUNCTION__, $days);
        // Notify enabled?
        $isDay = $this->ReadPropertyInteger(self::ALMANAC_DP[$days][2]);
        // Webfront configured?
        $wfc = $this->ReadPropertyInteger('InstanceWebfront');
        // Lookup
        if ($isDay && ($wfc != 0)) {
            try {
                // get format
                $format = $this->ReadPropertyString(self::ALMANAC_DP[$days][6]);
                $data = $this->LookupDays(time(), self::ALMANAC_DP[$days][1]);
                foreach ($data as $item) {
                    $output = $this->FormatDay($item, $format);
                    if ($this->IsWebFrontVisuInstance($wfc)) {
                        //TODO:Update if added
                        /** @phpstan-ignore-next-line */
                        WFC_PushNotification($wfc, $this->Translate('Date'), $output, 'Calendar', 0);
                        $this->LogDebug(__FUNCTION__, 'Send to Webfront');
                    }
                    if ($this->IsTileVisuInstance($wfc)) {
                        //TODO:Update if added
                        /** @phpstan-ignore-next-line */
                        VISU_PostNotificationEx($wfc, $this->Translate('Date'), $output, 'Calendar', 'happy', 0);
                        $this->LogDebug(__FUNCTION__, 'Send to TileVisu');
                    }
                }
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR: ' . $ex->getMessage());
            }
        }
        // Calculate next notification timer interval
        $data = json_decode($this->ReadPropertyString(self::ALMANAC_DP[$days][3]), true);
        $this->UpdateTimerInterval(self::ALMANAC_DP[$days][0], $data['hour'], $data['minute'], $data['second']);
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * ALMANAC_Update($id);
     *
     * @return void
     */
    public function Update(): void
    {
        // General Date
        $isHoliday = $this->ReadPropertyBoolean('UpdateHoliday');
        $isVacation = $this->ReadPropertyBoolean('UpdateVacation');
        $isFestive = $this->ReadPropertyBoolean('UpdateFestive');
        $isDate = $this->ReadPropertyBoolean('UpdateDate');
        // B-W-D-Days
        $isBirth = $this->ReadPropertyBoolean('UpdateBirthday');
        $isWedding = $this->ReadPropertyBoolean('UpdateWedding');
        $isDeath = $this->ReadPropertyBoolean('UpdateDeath');
        // E-M-Q
        $isEclipse = $this->ReadPropertyBoolean('UpdateEclipse');
        $isMoonphase = $this->ReadPropertyBoolean('UpdateMoonphase');
        $isQuote = $this->ReadPropertyBoolean('UpdateQuote');
        // MessageScript
        $script = $this->ReadPropertyInteger('ScriptMessage');
        // Everything to do?
        if ($isHoliday || $isVacation || $isFestive || $isBirth || $isWedding || $isDeath || $isEclipse || $isMoonphase || $isQuote || $isDate) {
            $info = $this->DateInfo(time());
            $date = json_decode($info, true);
            $this->SetCache('DateInfo', $info);
        } else {
            $this->ClearCache('DateInfo');
            return;
        }
        // Public Holidays
        if ($isHoliday == true) {
            try {
                $this->SetValueString('Holiday', $date['Holiday']);
                $this->SetValueBoolean('IsHoliday', $date['IsHoliday']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR HOLIDAY: ' . $ex->getMessage());
            }
        }
        // School Vacations
        if ($isVacation == true) {
            try {
                $this->SetValueString('Vacation', $date['Vacation']);
                $this->SetValueBoolean('IsVacation', $date['IsVacation']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR VACATION: ' . $ex->getMessage());
            }
        }
        // Festive Days
        if ($isFestive == true) {
            try {
                $this->SetValueString('Festive', $date['Festive']);
                $this->SetValueBoolean('IsFestive', $date['IsFestive']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR FESTIVE: ' . $ex->getMessage());
            }
        }
        // General Date Info
        if ($isDate == true) {
            try {
                $this->SetValueBoolean('IsSummer', $date['IsSummer']);
                $this->SetValueBoolean('IsLeapyear', $date['IsLeapYear']);
                $this->SetValueBoolean('IsWeekend', $date['IsWeekend']);
                $this->SetValueInteger('WeekDay', $date['Weekday']);
                $this->SetValueInteger('WeekNumber', $date['WeekNumber']);
                $this->SetValueInteger('DaysInMonth', $date['DaysInMonth']);
                $this->SetValueInteger('DayOfYear', $date['DayOfYear']);
                $this->SetValueInteger('WorkingDays', $date['WorkingDays']);
                $this->SetValueString('DayLong', $date['DayLong']);
                $this->SetValueString('Season', $date['Season']);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR DATE: ' . $ex->getMessage());
            }
        }
        // Birthdays
        if ($isBirth == true) {
            try {
                $this->UpdateDay(self::ALMANAC_DP[self::ALMANAC_BD], $date, $script);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR BIRTH: ' . $ex->getMessage());
            }
        }
        // Wedding days
        if ($isWedding == true) {
            try {
                $this->UpdateDay(self::ALMANAC_DP[self::ALMANAC_WD], $date, $script);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR WEDDING: ' . $ex->getMessage());
            }
        }
        // Death days
        if ($isDeath == true) {
            try {
                $this->UpdateDay(self::ALMANAC_DP[self::ALMANAC_DD], $date, $script);
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR DEATH: ' . $ex->getMessage());
            }
        }
        // Eclipse event
        if ($isEclipse == true) {
            try {
                $this->SetValueBoolean('IsEclipse', $date['IsEclipse']);
                if (count($date['Eclipse']) > 0) {
                    $format = $this->ReadPropertyString('EclipseFormat');
                    $this->SetValueString('Eclipse', $this->FormatEvent($date['Eclipse'], $format));
                } else {
                    $this->SetValueString('Eclipse', '');
                }
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR ECLIPSE: ' . $ex->getMessage());
            }
        }
        // Moonphase event
        if ($isMoonphase == true) {
            try {
                $this->SetValueBoolean('IsMoonphase', $date['IsMoonphase']);
                if (count($date['Moonphase']) > 0) {
                    $format = $this->ReadPropertyString('MoonphaseFormat');
                    $this->SetValueString('Moonphase', $this->FormatEvent($date['Moonphase'], $format));
                } else {
                    $this->SetValueString('Moonphase', '');
                }
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR Moonphase: ' . $ex->getMessage());
            }
        }
        // Quote of the day
        if ($isQuote == true) {
            try {
                $format = $this->ReadPropertyString('QuoteFormat');
                $this->SetValueString('QuoteOfTheDay', $this->FormatQuote($date['QuoteOfTheDay'], $format));
            } catch (Exception $ex) {
                $this->LogMessage($ex->getMessage(), KL_ERROR);
                $this->LogDebug(__FUNCTION__, 'ERROR QuoteOfTheDay: ' . $ex->getMessage());
            }
        }
        // Update visualization
        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
        // calculate next update interval
        $this->UpdateTimerInterval('UpdateTimer', 0, 0, 30);
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
        $this->LogDebug(__FUNCTION__, 'DATE: ' . date('d.m.Y', $ts));
        // Output array
        $date = [];
        $now = date('Ymd', $ts);
        $year = date('Y', $ts);

        // --------------------------------------------------------------------
        // simple date infos
        // --------------------------------------------------------------------
        $date['IsSummer'] = boolval(date('I', $ts));
        $date['IsLeapYear'] = boolval(date('L', $ts));
        $date['IsWeekend'] = boolval(date('N', $ts) > 5);
        $date['Weekday'] = intval(date('N', $ts));
        $date['WeekNumber'] = idate('W', $ts);
        $date['DaysInMonth'] = idate('t', $ts);
        $date['DayOfYear'] = idate('z', $ts) + 1; // idate('z') is zero based
        $date['DayLong'] = $this->FormatLong($ts, $this->ReadPropertyString('DateFormat'));

        // --------------------------------------------------------------------
        // season info
        // --------------------------------------------------------------------
        $date['Season'] = $this->Season($ts);

        // --------------------------------------------------------------------
        // get festive days
        // --------------------------------------------------------------------
        $isFestive = $this->LookupCalendar($ts);
        $date['Festive'] = $isFestive;
        $date['IsFestive'] = ($isFestive == $this->ReadPropertyString('NoFestive')) ? false : true;

        // --------------------------------------------------------------------
        // get birthdays
        // --------------------------------------------------------------------
        $isBirth = $this->LookupDays($ts, self::ALMANAC_DP[self::ALMANAC_BD][1]);
        $date['Birthday'] = $isBirth;
        $date['IsBirthday'] = (count($isBirth) == 0) ? false : true;

        // --------------------------------------------------------------------
        // get weddingdays
        // --------------------------------------------------------------------
        $isWedding = $this->LookupDays($ts, self::ALMANAC_DP[self::ALMANAC_WD][1]);
        $date['Weddingday'] = $isWedding;
        $date['IsWeddingday'] = (count($isWedding) == 0) ? false : true;

        // --------------------------------------------------------------------
        // get deathdays
        // --------------------------------------------------------------------
        $isDeath = $this->LookupDays($ts, self::ALMANAC_DP[self::ALMANAC_DD][1]);
        $date['Deathday'] = $isDeath;
        $date['IsDeathday'] = (count($isDeath) == 0) ? false : true;

        // --------------------------------------------------------------------
        // get holiday data
        // --------------------------------------------------------------------
        $country = $this->ReadPropertyString('PublicCountry');
        $region = $this->ReadPropertyString('PublicRegion');
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
        $isHoliday = $this->ReadPropertyString('NoHoliday');
        foreach ($data as $entry) {
            if (($now >= $entry['start']) && ($now < $entry['end'])) {
                $isHoliday = $entry['event'];
                $this->LogDebug(__FUNCTION__, 'HOLIDAY: ' . $isHoliday);
                break;
            }
        }
        $date['Holiday'] = $isHoliday;
        $date['IsHoliday'] = ($isHoliday == $this->ReadPropertyString('NoHoliday')) ? false : true;
        // no data, no info
        if (empty($data)) {
            $date['Holiday'] = $this->Translate('Holiday not determined');
            $date['IsHoliday'] = false;
        }

        // --------------------------------------------------------------------
        // get vacation data
        // --------------------------------------------------------------------
        $period = $this->ReadPropertyBoolean('SchoolPeriod');
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
        if ((int) date('md', $ts) < 110) {
            $prev = $year - 1;
            $link = str_replace('YEAR', (string) $prev, $url);
            $data0 = $this->ExtractDates($link);
        } else {
            $data0 = [];
        }
        $link = str_replace('YEAR', $year, $url);
        $data1 = $this->ExtractDates($link);
        $data = array_merge($data0, $data1);
        $this->LogDebug(__FUNCTION__, $data);
        $isVacation = $this->ReadPropertyString('NoVacation');
        foreach ($data as $entry) {
            if (($now >= $entry['start']) && ($now < $entry['end'])) {
                $isVacation = explode(' ', $entry['event'])[0];
                $this->LogDebug(__FUNCTION__, 'VACATION: ' . $isVacation);
                if ($period) {
                    $sp = substr($entry['start'], 6, 2) . '.' . substr($entry['start'], 4, 2) . '.' . substr($entry['start'], 0, 4);
                    $ep = substr($entry['end'], 6, 2) . '.' . substr($entry['end'], 4, 2) . '.' . substr($entry['end'], 0, 4);
                    $isVacation .= ' (' . $sp . '-' . $ep . ')';
                }
                break;
            }
        }
        $date['Vacation'] = $isVacation;
        $date['IsVacation'] = ($isVacation == $this->ReadPropertyString('NoVacation')) ? false : true;
        // no data, no info
        if (empty($data)) {
            $date['Vacation'] = $this->Translate('Vacation not determined');
            $date['IsVacation'] = false;
        }

        // --------------------------------------------------------------------
        // get eclipse
        // --------------------------------------------------------------------
        $url = $this->ReadAttributeString('AstroURL');
        // prepeare API-URL (fix DE)
        $link = str_replace('YEAR', $year, $url);
        $link = str_replace('COUNTRY', 'de', $link);
        $link = str_replace('EVENT', 'eclipses', $link);
        $data = $this->ExtractDates($link);
        $isEclipse = [];
        $hit = false;
        foreach ($data as $entry) {
            if ($now <= $entry['date']) {
                $this->LogDebug(__FUNCTION__, 'ECLIPSE: ' . $entry['name']);
                $ed = substr($entry['date'], 6, 2) . '.' . substr($entry['date'], 4, 2) . '.' . substr($entry['date'], 0, 4);
                $isEclipse = ['name' => $entry['name'], 'date' => $ed, 'time' => date('H:i', intval($entry['time']))];
                if ($now == $entry['date']) {
                    $hit = true;
                }
                break;
            }
        }
        $date['Eclipse'] = $isEclipse;
        $date['IsEclipse'] = $hit;

        // --------------------------------------------------------------------
        // get moon phase
        // --------------------------------------------------------------------
        // prepeare API-URL (fix DE)
        $link = str_replace('YEAR', $year, $url);
        $link = str_replace('COUNTRY', 'de', $link);
        $link = str_replace('EVENT', 'phases', $link);
        $data = $this->ExtractDates($link);
        $isMoonphase = [];
        $hit = false;
        foreach ($data as $entry) {
            if ($now <= $entry['date']) {
                $this->LogDebug(__FUNCTION__, 'MOONPHASE: ' . $entry['name']);
                $md = substr($entry['date'], 6, 2) . '.' . substr($entry['date'], 4, 2) . '.' . substr($entry['date'], 0, 4);
                $isMoonphase = ['name' => $entry['name'], 'date' => $md, 'time' => date('H:i', intval($entry['time']))];
                if ($now == $entry['date']) {
                    $hit = true;
                }
                break;
            }
        }
        $date['Moonphase'] = $isMoonphase;
        $date['IsMoonphase'] = $hit;

        // --------------------------------------------------------------------
        // get quote of the day
        // --------------------------------------------------------------------
        $url = $this->ReadAttributeString('QuoteURL');
        // prepeare API-URL (fix DE)
        $link = str_replace('COUNTRY', 'de', $url);
        $data = $this->ExtractDates($link, 'quotes');
        $count = count($data);
        $qotd = random_int(0, $count - 1);
        $this->LogDebug(__FUNCTION__, 'QOTD: #' . $qotd);
        $date['QuoteOfTheDay'] = ['quote' => $data[$qotd]['quote'], 'author' => $data[$qotd]['author']];

        // --------------------------------------------------------------------
        // dump result
        // --------------------------------------------------------------------
        $this->LogDebug(__FUNCTION__ . ':DATA', $date);

        // --------------------------------------------------------------------
        // return date info as json
        // --------------------------------------------------------------------
        return json_encode($date);
    }

    /**
     * User has selected a new country.
     *
     * @param string $cid Country ID.
     * @return void
     */
    protected function OnPublicCountry(string $cid): void
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
     * @return void
     */
    protected function OnSchoolCountry(string $cid): void
    {
        // Get Data
        $data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);
        // Region Options
        $region = $data[$cid][0]['regions'][0]['ident'];
        $this->LogDebug(__FUNCTION__, 'REGION: ' . $region);
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
     * @return void
     */
    protected function OnSchoolRegion(string $region): void
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
     * @return void
     */
    protected function OnImportBirthdays(string $value): void
    {
        $this->ImportCSV('Birthdays', $value);
    }

    /**
     * Import wedding days data.
     *
     * @param string $value Base64 coded data.
     * @return void
     */
    protected function OnImportWeddingdays(string $value): void
    {
        $this->ImportCSV('Weddingdays', $value);
    }

    /**
     * Import death days data.
     *
     * @param string $value Base64 coded data.
     * @return void
     */
    protected function OnImportDeathdays(string $value): void
    {
        $this->ImportCSV('Deathdays', $value);
    }

    /**
     * Clear the selected days list.
     *
     * @param string $value property name.
     * @return void
     */
    protected function OnDeleteDays(string $value): void
    {
        $this->LogDebug(__FUNCTION__, $value);
        // with days
        $property = self::ALMANAC_DP[$value][1];
        $data = [];
        $this->UpdateFormField($property, 'values', json_encode($data));
    }

    /**
     * This function will be called by the hook control. Visibility should be protected!
     *
     * @return void
     */
    protected function ProcessHookData(): void
    {
        //$this->LogDebug(__FUNCTION__, $_GET);
        $export = $_GET['export'] ?? '';
        //$this->LogDebug(__FUNCTION__, 'Export: ' . $export);
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
        $this->LogDebug(__FUNCTION__, $this->ReadPropertyString($property));
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
     * Generate a message that updates all elements in the HTML display.
     *
     * @return string JSON encoded message information
     */
    private function GetFullUpdateMessage(): string
    {
        // Configuration for the visualization, e.g. number of columns and slot configuration
        $this->LogDebug(__FUNCTION__, $this->ReadPropertyString('Slots'));

        $config = json_decode($this->ReadPropertyString('Slots'), true) ?? [];

        $configuration = [
            'Desktop' => $this->ReadPropertyInteger('Desktop'),
            'Tablet'  => $this->ReadPropertyInteger('Tablet'),
            'Mobile'  => $this->ReadPropertyInteger('Mobile'),
            'Slots'   => $config,
        ];

        // Rohdaten vom Almanac holen
        $info = json_decode($this->GetCache('DateInfo'), true);

        // Falls der Cache leer ist, einmal neu erzeugen
        if (empty($info)) {
            $this->LogMessage('Almanac: Cache empty - rebuilding.', KL_DEBUG);

            $this->Update();

            // Cache erneut lesen
            $info = json_decode($this->GetCache('DateInfo'), true);

            // Falls immer noch keine Daten vorhanden sind, leeres Ergebnis zurückgeben
            if (empty($info)) {
                $this->LogMessage('Almanac: Update() did not provide any data.', KL_WARNING);
                return json_encode([]);
            }
        }

        // ── Arrays zu lesbaren Strings aufbereiten ──────────────────────────

        // Birthday: mehrere Einträge möglich
        $birthday = '';
        if (!empty($info['Birthday']) && is_array($info['Birthday'])) {
            $parts = [];
            foreach ($info['Birthday'] as $b) {
                $parts[] = $b['name'] . ' (' . $b['years'] . ' J.)';
            }
            $birthday = implode(', ', $parts);
        }

        // Weddingday
        $weddingday = '';
        if (!empty($info['Weddingday']) && is_array($info['Weddingday'])) {
            $parts = [];
            foreach ($info['Weddingday'] as $w) {
                $parts[] = $w['name'] . ' (' . $w['years'] . ' J.)';
            }
            $weddingday = implode(', ', $parts);
        }

        // Deathday
        $deathday = '';
        if (!empty($info['Deathday']) && is_array($info['Deathday'])) {
            $parts = [];
            foreach ($info['Deathday'] as $d) {
                $parts[] = $d['name'] . ' (' . $d['years'] . ' J.)';
            }
            $deathday = implode(', ', $parts);
        }

        // Eclipse: Typ + Datum
        $eclipse = '';
        if (!empty($info['Eclipse']) && is_array($info['Eclipse'])) {
            $e = $info['Eclipse'];
            $eclipse = $e['name'] . ', ' . $e['date'];
        }

        // Moonphase: Name + Datum + Uhrzeit
        $moonphase = '';
        if (!empty($info['Moonphase']) && is_array($info['Moonphase'])) {
            $m = $info['Moonphase'];
            $moonphase = $m['name'] . ', ' . $m['date'] . ' ' . substr($m['time'], 0, 5);
        }

        // Quote: Zitat + Autor
        $quote = '';
        if (!empty($info['QuoteOfTheDay'])) {
            $q = $info['QuoteOfTheDay'];
            $quote = '„' . $q['quote'] . '" — ' . $q['author'];
        }

        // Field names must match exactly with getVar() calls in module.html
        $data = [
            // Boolean-Flags
            'IsSummer'      => (bool) $info['IsSummer'],
            'IsLeapYear'    => (bool) $info['IsLeapYear'],
            'IsWeekend'     => (bool) $info['IsWeekend'],
            'IsFestive'     => (bool) $info['IsFestive'],
            'IsHoliday'     => (bool) $info['IsHoliday'],
            'IsVacation'    => (bool) $info['IsVacation'],
            'IsBirthday'    => (bool) $info['IsBirthday'],
            'IsWeddingday'  => (bool) $info['IsWeddingday'],
            'IsDeathday'    => (bool) $info['IsDeathday'],
            'IsEclipse'     => (bool) $info['IsEclipse'],
            'IsMoonphase'   => (bool) $info['IsMoonphase'],

            // Anzeigewerte
            'Season'        => (string) $this->Translate($info['Season']),
            'DayLong'       => (string) $info['DayLong'],
            'Festive'       => (string) $info['Festive'],
            'Holiday'       => (string) $info['Holiday'],
            'Vacation'      => (string) $info['Vacation'],
            'WeekNumber'    => (int) $info['WeekNumber'],
            'DaysInMonth'   => (int) $info['DaysInMonth'],
            'DayOfYear'     => (int) $info['DayOfYear'],
            'WorkingDays'   => (int) $info['WorkingDays'],

            // Aufbereitete Array-Werte
            'Birthday'      => $birthday,
            'Weddingday'    => $weddingday,
            'Deathday'      => $deathday,
            'Eclipse'       => $eclipse,
            'Moonphase'     => $moonphase,
            'QuoteOfDay'    => $quote,
        ];
        // Return both together
        $result = [
            'config' => $configuration,
            'data'   => $data,
        ];
        $this->LogDebug(__FUNCTION__, $result);
        return json_encode($result);
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
        $year = intval(date('Y', $ts));
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
            //$this->LogDebug(__FUNCTION__, $text.' - '.$date['name']);
        }
        // lookup for given date
        $day = date('Ymd', $ts);
        if (array_key_exists($day, $dates)) {
            return $dates[$day];
        }
        return $this->ReadPropertyString('NoFestive');
    }

    /**
     * Lookup for Birth-, Wedding, Death-Days
     *
     * @return list<array{date:string,years:int,name:string}> Name of a feast day for a given timestamp.
     */
    private function LookupDays(int $ts, string $property): array
    {
        // 1 = 'Deathdays', 5 = 'DeathdayDuration', 6 = 'DeathdayFormat'
        $year = intval(date('Y', $ts));
        $day = date('j', $ts);
        $mon = date('n', $ts);
        // get the current entries
        $list = json_decode($this->ReadPropertyString($property), true);
        if (empty($list) || !is_array($list)) {
            $list = [];
        }
        // build value list
        $entry = [];
        foreach ($list as $key => $item) {
            if (is_array($item)) {
                $dt = json_decode($item['Date'], true);
                if ($day == $dt['day'] && $mon == $dt['month']) {
                    $date = $dt['day'] . '.' . $dt['month'] . '.' . $dt['year'];
                    $years = $year - $dt['year'];
                    $entry[] = ['date' => $date, 'years' => $years, 'name' => $item['Name']];
                }
            }
        }
        return $entry;
    }

    /**
     * Format a given array to a string.
     *
     * @param array{date:string,years:int,name:string} $item Date event item
     * @param string $format Format string
     * @return string Formated date
     */
    private function FormatDay(array $item, string $format): string
    {
        $now = date('d.m.Y', time());
        $output = str_replace('%E', $item['date'], $format);
        $output = str_replace('%Y', (string) $item['years'], $output);
        $output = str_replace('%N', $item['name'], $output);
        $output = str_replace('%D', $now, $output);
        return $output;
    }

    /**
     * Format a given timestamp in a string.
     *
     * @param int $now timestamp
     * @param string $format Format string
     *
     * @return string Formated timestamp
     */
    private function FormatLong(int $now, string $format): string
    {
        // format: %j,%d,%D,%l = day, %n,%m,%M,%F = month. $y,%Y = year)
        $output = str_replace('%d', $this->Translate(date('d', $now)), $format);
        $output = str_replace('%l', $this->Translate(date('l', $now)), $output);
        $output = str_replace('%M', $this->Translate(date('M', $now)), $output);
        $output = str_replace('%F', $this->Translate(date('F', $now)), $output);
        $output = str_replace('%j', date('j', $now), $output);
        $output = str_replace('%D', date('D', $now), $output);
        $output = str_replace('%n', date('n', $now), $output);
        $output = str_replace('%m', date('m', $now), $output);
        $output = str_replace('%y', date('y', $now), $output);
        $output = str_replace('%Y', date('>', $now), $output);
        $this->LogDebug(__FUNCTION__, 'Result : ' . $output);
        return $output;
    }

    /**
     * Format a given array to a string.
     *
     * @param array<string,string> $item Event item
     * @param string $format Format string
     * @return string Formated event
     */
    private function FormatEvent(array $item, $format): string
    {
        $output = str_replace('%N', $item['name'], $format);
        $output = str_replace('%D', $item['date'], $output);
        $output = str_replace('%T', $item['time'], $output);
        return $output;
    }

    /**
     * Format the given quotes array to a string.
     *
     * @param array<string,string> $item Event item
     * @param string $format Format string
     * @return string Formated quote of the day
     */
    private function FormatQuote(array $item, string $format): string
    {
        $output = str_replace('%Q', $item['quote'], $format);
        $output = str_replace('%A', $item['author'], $output);
        return $output;
    }

    /**
     * Update specific days-variable / dashboard.
     *
     * @param list<string> $property Day property idents.
     * @param array{IsSummer:bool,IsLeapYear:bool,IsWeekend:bool,Weekday:int,WeekNumber:int,DaysInMonth:int,DayOfYear:int,DayLong:string,Season:string,Festive:string,IsFestive:bool,WorkingDays:int,Holiday:string,IsHoliday:bool,Vacation:string,IsVacation:bool,IsBirthday:bool,Birthday:list<array{date:string,years:int,name:string}>,IsWeddingday:bool,Weddingday:list<array{date:string,years:int,name:string}>,IsDeathday:bool,Deathday:list<array{date:string,years:int,name:string}>,IsEclipse:bool,Eclipse:list<array{name:string, date:string,time:string}>,IsMoonphase:bool,Moonphase:list<array{name:string,date:string,time:string}>,QuoteOfTheDay:list<array{quote:string,author:string}>} $date Info for the day
     * @param int $script Script ID
     * @return void
     */
    private function UpdateDay(array $property, array $date, int $script): void
    {
        // time
        $time = $this->ReadPropertyInteger($property[5]);
        // format
        $format = $this->ReadPropertyString($property[6]);
        // variable
        $variable = $this->ReadPropertyInteger($property[7]);
        // seperator
        $separator = $this->ReadPropertyString($property[8]);
        // no event text
        $nothing = $this->ReadPropertyString($property[9]);
        // date array
        $ident = substr($property[1], 0, -1);
        $items = $date[$ident];
        $length = count($items);
        $lines = '';
        $index = 1;
        // iterate
        foreach ($items as $item) {
            // format date item
            $output = $this->FormatDay($item, $format);
            // send to dashboard
            if ($script != 0) {
                if ($time > 0) {
                    $msg = IPS_RunScriptWaitEx($script, ['action' => 'add', 'text' => $output, 'expires' => time() + $time, 'removable' => true, 'type' => 4, 'image' => 'Calendar']);
                } else {
                    $msg = IPS_RunScriptWaitEx($script, ['action' => 'add', 'text' => $output, 'removable' => true, 'type' => 4, 'image' => 'Calendar']);
                }
            }
            // collect for variable
            if ($index < $length) {
                $lines .= $output . $separator;
            } else {
                $lines .= $output;
            }
            $index++;
        }
        // write to variable
        if ($variable) {
            if ($lines !== '') {
                $this->SetValueString($ident, $lines);
            } else {
                $this->SetValueString($ident, $nothing);
            }
            $ident = 'Is' . $ident;
            $this->SetValueBoolean($ident, $date[$ident]);
        }
    }

    /**
     * Get and extract dates from iCal format.
     *
     * @param string $property Name of the list element
     * @param string $value Data to import (base64 coded)
     * @return void
     */
    private function ImportCSV(string $property, string $value): void
    {
        $csv = base64_decode($value);
        $lines = preg_split('/[\r\n]{1,2}(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/', $csv);
        $data = [];
        foreach ($lines as $row) {
            $data[] = str_getcsv($row);
        }
        // check ... was comma
        $cols = max(array_map('count', $data));
        if ($cols != 2) {
            $data = [];
            foreach ($lines as $row) {
                $data[] = str_getcsv($row, ';');
            }
        }
        // check ... was semicolon
        $cols = max(array_map('count', $data));
        if ($cols != 2) {
            $this->LogDebug(__FUNCTION__, 'No CSV format found!');
            return;
        }
        // get the current entries
        $list = json_decode($this->ReadPropertyString($property), true);
        if (empty($list) || !is_array($list)) {
            $list = [];
        }
        // build value list
        $entry = [];
        foreach ($data as $key => $item) {
            if (isset($item[0])) {
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
        $this->UpdateFormField($property, 'values', json_encode($data));
    }

    /**
     * Get and extract dates from json format.
     *
     * @param string $url API URL to receive event information.
     * @return list<array{quote:string,author:string}|array{event:string,start:string,end:string}|array{name:string,desc:string,date:string,time:string}>
     */
    private function ExtractDates(string $url, string $info = 'events'): array
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, 'LINK: ' . $url);

        // Get cache data
        $cache = json_decode($this->GetCache('UrlCache'), true);
        if (!is_array($cache)) {
            $cache = [];
        }
        // Get cache time per url
        $timeout = $this->GetCacheTimeoutForUrl($url);

        // Can read from cache?
        if (isset($cache[$url])) {
            $entry = $cache[$url];
            if ($timeout === 0 || ($entry['timestamp'] + $timeout) > time()) {
                $this->LogDebug(__FUNCTION__, 'Cache hit [' . ($timeout === 0 ? '∞' : round($timeout / 60) . ' min') . ']');
                return $entry['result'];
            }
        }

        // Read from API
        $json = @file_get_contents($url);
        // Error handling
        if ($json === false) {
            $this->LogMessage($this->Translate('Could not load json data!'), KL_ERROR);
            $this->LogDebug(__FUNCTION__, 'ERROR LOAD DATA');
            return [];
        }

        // JSON decode
        $data = json_decode($json, true);
        if (!isset($data['data'][$info])) {
            $this->LogDebug(__FUNCTION__, 'NO DATA FOUND');
            return [];
        }

        // We have data
        $result = $data['data'][$info];

        $cache[$url] = [
            'result'    => $result,
            'timestamp' => time()
        ];
        $this->SetCache('UrlCache', json_encode($cache));
        $this->LogDebug(__FUNCTION__, 'Cache miss - new stroed [' . ($timeout === 0 ? '∞' : round($timeout / 60) . ' min') . ']');

        // Return the events
        return $result;
    }

    /**
     * Returns the cache timeout for a given url
     *
     * @param string $url Passed Url
     * @return int Cache time in seconds
     */
    private function GetCacheTimeoutForUrl(string $url): int
    {
        foreach (self::ALMANAC_CACHE_RULES as $pattern => $seconds) {
            if (strpos($url, $pattern) !== false) {
                return $seconds;
            }
        }
        // Fallback: 1 day
        return self::ALMANAC_SECONDS_PER_DAY;
    }

    /**
     * Reads the public regions for a given country.
     *
     * @param list<array{country:string,part:string,regions:list<array{name:string,ident:string,schools:list<array{name:string,ident:string}>}>}> $country Country data array.
     * @return list<array{caption:string,value:string}> Regions options array.
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
     * @param list<array{country:string,part:string,regions:list<array{name:string,ident:string,schools:list<array{name:string,ident:string}>}>}> $country Country data array.
     * @param string $region region ident.
     *
     * @return list<array{caption:string,value:string}> School options array.
     */
    private function GetSchool(array $country, string $region): array
    {
        $this->LogDebug(__FUNCTION__, $country, false);
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
}
