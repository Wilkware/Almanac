# Almanac (Jahreskalender)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-5.2-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-4.0.20210214-orange.svg)](https://github.com/Wilkware/IPSymconAlmanac)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/IPSymconAlmanac/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/IPSymconAlmanac/actions)

Dieses Modul bietet jährliche Kalenderinformationen wie Feiertage, Schulferien und Festtage.  
Außerdem werden Informationen wie Arbeitstage im Monat, Schaltjahr, Jahreszeit oder ob Wochenende aktuell gehalten.
Darüber hinaus kann man Geburtstage, Hochzeitstage und Todestage verwalten und sich täglich informieren lassen.

## Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Versionshistorie](#8-versionshistorie)

### 1. Funktionsumfang

Das Modul nutzt eine eigens entwickelte JSON-API (CDN basierend) um die Daten für Feiertage und Schulferien
in Deutschland, Österreich und der Schweiz bereitzustellen.  
Derzeit unterstützt das Modul auch eine Vielzahl verschiedenster religöser und weltlicher Festtage (z.B. Valentinstag oder Kindertag).  
Als Gedächtnisstütze können die jährlichen Geburtstage, Hochzeitstage aber auch Todestage verwaltet werden und man
kann sich täglich informieren lassen ob ein Termin ansteht (Meldungsverwaltung oder via Webfront-Notification).  
Darüber hinaus werden mittels der PHP Funktion "date" verschiedene Informationen für das aktuelle Datum ermittelt.  
In Kombination mit den ermittelten Feiertagen werden auch die Arbeitstage im aktuellen Monat bereitgestellt.

Folgende Informationen werden ermittelt:

* Sind Ferien und welche
* Feiertag oder nicht und wie heißt er
* Der Tag des Jahres
* Anzahl Tage im Monat
* Arbeitstage im Monat
* Schaltjahr oder nicht
* Sommerzeit oder nicht
* Wochenende oder nicht
* Nummer der Kalenderwoche
* Jahreszeit (Frühling, Sommer, Herbst und Winter)

All diese Information können auch über die Methode [ALMANAC_DateInfo](#7-php-befehlsreferenz) als Array abgeholt werden.

Folgende Informationen stehen als key => value Paare zur Verfügung:

Index                 | Typ     | Beschreibung
--------------------- | ------- | ----------------
IsSummer              | bool    | TRUE, wenn Sommerzeit ist
IsLeapYear            | bool    | TRUE, wenn Schaltjahr ist
IsWeekend             | bool    | TRUE, wenn Wochenende ist (SA-SO)
WeekNumber            | int     | Kalenderwochennummer
DaysInMonth           | int     | Anzahl Tage im Monat
DayOfYear             | int     | Tag im Jahr (1-366)
Season                | string  | "Frühling", "Sommer", "Herbst" oder "Winter"
Festive               | string  | Name des Festtags, oder "Kein Festtag"
IsFestive             | bool    | TRUE, wenn Festtag ist
WorkingDays           | int     | Arbeitstage im Monat
Holiday               | string  | Name des Feiertags, oder "Kein Feiertag"
IsHoliday             | bool    | TRUE, wenn Feiertag ist
Vacation              | string  | Name der Schulferien, oder "Keine Ferien"
IsVacation            | bool    | TRUE, wenn Schulferienzeit ist

### 2. Voraussetzungen

* IP-Symcon ab Version 5.2

### 3. Installation

* Über den Modul Store das Modul Almanac installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconAlmanac` oder `git://github.com/Wilkware/IPSymconAlmanac.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das 'Almanac'-Modul (Alias: Jahreskalender, Almanach) unter dem Hersteller '(Sonstige)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> Feiertage ...

Name               | Beschreibung
------------------ | ---------------------------------
Land               | Auswahl des Landes (Deutschland, Österreich und Schweiz).
Bundesland         | Auswahl des Bundeslandes/Karton für welchen man die Feiertage ermittelt haben möchte.

> Schulferien ...

Name               | Beschreibung
------------------ | ---------------------------------
Land               | Auswahl des Landes (Deutschland, Österreich und Schweiz).
Bundesland         | Auswahl des Bundeslandes/Karton für welchen man die Schulferien ermittelt haben möchte.
Schulen            | Derzeit nur für die Schweiz entscheidend, Auswahl der gewünschten Schule im Kanton.

> Geburtstage ...

Name                          | Beschreibung
----------------------------- | ---------------------------------
Termine                       | Eingabe des Geburtstermins (Tag.Monat.Jahr) und den dazugehörigen Namen
Nachricht ans Webfront senden | Auswahl ob Push-Nachricht gesendet werden soll oder nicht (Ja/Nein)
Nachricht Sendezeit           | Uhrzeit wann täglich die Nachricht gesendet weden soll
Meldung an Anzeige senden     | Auswahl ob Eintrag in die Meldungsverwaltung erfolgen soll oder nicht (Ja/Nein)
Lebensdauer der Nachricht     | Wie lange so die Meldung angezeigt werden?
Format der Textmitteilung     | Frei wählbares Format der zu sendenden Nachricht/Meldung

> Hochzeitstage ...

Name                          | Beschreibung
----------------------------- | ---------------------------------
Termine                       | Eingabe Heiratstermins (Tag.Monat.Jahr) und den dazugehörigen Namen
Nachricht ans Webfront senden | Auswahl ob Push-Nachricht gesendet werden soll oder nicht (Ja/Nein)
Nachricht Sendezeit           | Uhrzeit wann täglich die Nachricht gesendet weden soll
Meldung an Anzeige senden     | Auswahl ob Eintrag in die Meldungsverwaltung erfolgen soll oder nicht (Ja/Nein)
Lebensdauer der Nachricht     | Wie lange so die Meldung angezeigt werden?
Format der Textmitteilung     | Frei wählbares Format der zu sendenden Nachricht/Meldung

> Todestage ...

Name                          | Beschreibung
----------------------------- | ---------------------------------
Termine                       | Eingabe Sterbetag (Tag.Monat.Jahr) und den dazugehörigen Namen
Nachricht ans Webfront senden | Auswahl ob Push-Nachricht gesendet werden soll oder nicht (Ja/Nein)
Nachricht Sendezeit           | Uhrzeit wann täglich die Nachricht gesendet weden soll
Meldung an Anzeige senden     | Auswahl ob Eintrag in die Meldungsverwaltung erfolgen soll oder nicht (Ja/Nein)
Lebensdauer der Nachricht     | Wie lange so die Meldung angezeigt werden?
Format der Textmitteilung     | Frei wählbares Format der zu sendenden Nachricht/Meldung

> Erweiterte Einstellungen ...

Name                                      | Beschreibung
----------------------------------------- | ---------------------------------
Feirtage ermitteln                        | Status, ob Ermittlung der Feiertage erwünscht ist
Schulferien ermitteln                     | Status, ob Ermittlung der Schulferien erwünscht ist
Festtage ermitteln                        | Status, ob Ermittlung der Festtage erwünscht ist
Geburtstage ermitteln                     | Status, ob Geburtstage ausgewertet werden sollen
Hochzeitstage ermitteln                   | Status, ob Hochzeitstage ausgewertet werden sollen
Todestage ermitteln                       | Status, ob Todesstage ausgewertet werden sollen
Information zum aktuellen Datum ermitteln | Status, ob Informationen zum aktuellen Datum erwünscht sind.
Webfront Instanz                          | ID des Webfronts, an welches die Push-Nachrichten für Geburts-, Hochzeits- und Todestage gesendet werden soll
Meldsungsscript                           | Skript ID des Meldungsverwaltungsscripts, weiterführende Infos im Forum: [Meldungsanzeige im Webfront](https://community.symcon.de/t/meldungsanzeige-im-webfront/23473)

Aktionsbereich:

> Import & Export von ...

Aktion         | Beschreibung
-------------- | ------------------------------------------------------------
GEBURTSTAGE    | Öffnet Popup für die Möglichkeit zum Import/Export der Geburtstagsliste als CSV Datei (geburtstage.csv)
HOCHZEITSTAGE  | Öffnet Popup für die Möglichkeit zum Import/Export der Hochzeitsliste als CSV Datei (hochzeitstage.csv)
TODESTAGE      | Öffnet Popup für die Möglichkeit zum Import/Export der Sterbeliste als CSV Datei (todestage.csv)

_Hinweis:_ CSV-Format ist Termin, Name => 1.1.1970,"Herr Max Mustermann"

> Tagesdaten ...

Aktion         | Beschreibung
-------------- | ------------------------------------------------------------
AKTUALISIEREN  | Ermittelt für das aktuelle Datum alle Informationen (Update)

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
Ist Feiertag?        | Boolean   | Ist aktueller Tag ein Feiertag?
Ist Ferienzeit?      | Boolean   | Fällt aktueller Tag in die Ferien?
Ist Sommerzeit?      | Boolean   | Ist aktuell Sommerzeit aktiv?
Ist Schaltjahr?      | Boolean   | Ist aktueller Jahr ein Schaltjahr?
Ist Wochenende?      | Boolean   | Ist gerade Wochenende?
Ist Festtag?         | Boolean   | Ist aktueller Tag ein Festtag?
Feiertag             | String    | Name des Feriertages oder 'Kein Feiertag'
Ferien               | String    | Name der Schulferien oder 'Keine Ferien'
Festtag              | String    | Name des Festtages oder 'Kein Festtag'
Kalenderwoche        | Integer   | Nummer der aktuelle Kalenderwoche
Tage im Monat        | Integer   | Wieviel Tage hat der aktuelle Monat?
Tag im Jahr          | Integer   | Welcher Tag des Jahres?
Arbeitstage im Monat | Integer   | Wieviel Arbeitstage hat der Monat des gewählten Bundeslandes?
Jahreszeit           | String    | "Frühling", "Sommer", "Herbst" oder "Winter"

Folgende Profile werden angelegt:

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
ALMANAC.Question     | Boolean   | FALSE = Nein / TRUE = Ja

### 6. WebFront

Man kann die Statusvariablen direkt im WF verlinken.

### 7. PHP-Befehlsreferenz

```php
void ALMANAC_Update(int $InstanzID):
```

Holt entsprechend der Konfiguration die gewählten Daten.  
Die Funktion liefert keinerlei Rückgabewert.

__Beispiel__: `ALMANAC_Update(12345);`

```php
string ALMANAC_DateInfo(int $InstanzID, int $Timestamp);
```

Gibt für das übergebene Datum (Unix Timestamp) alle Informationen als assoziatives Array zurück.
__HINWEIS:__ Das Datum sollte nur maximal +/- 1 Jahr vom aktuellen Tag entfernt liegen.

__Beispiel__: `ALMANAC_DateInfo(12345, time());`

> {  
> "IsSummer": false,  
> "IsLeapYear": false,  
> "IsWeekend": true,  
> "WeekNumber": 6,  
> "DaysInMonth": 28,  
> "DayOfYear": 45,  
> "Season": "Winter",  
> "Festive": "Valentinstag",  
> "IsFestive": true,  
> "WorkingDays": 20,  
> "Holiday": "Kein Feiertag",  
> "IsHoliday": false,  
> "Vacation": "Keine Ferien",  
> "IsVacation": false  
}  

### 8. Versionshistorie

v4.0.20210214

* _NEU_: Eigener Webservice (JSON-API) für Ferien und Feiertage in DE, AT und CH (aktuell 2015 - 2022)
* _NEU_: Ermittung von verschiedensten religösen und weltlichen Festtagen
* _NEU_: Ermittlung der aktuellen Jahreszeit ("Frühling", "Sommer", "Herbst" oder "Winter")
* _NEU_: Verwaltung und Meldung von Geburtstagen (Liste)
* _NEU_: Verwaltung und Meldung von Hochzeitstagen (Liste)
* _NEU_: Verwaltung und Meldung von Todesstagen (Liste)
* _NEU_: Import & Export Funktionalität für Geburts-, Hochzeits- und Todestage
* _FIX_: Struktur DateInfo erweitert und Teile umbenannt
* _FIX_: Modul Aliase auf Jahreskalender und Almanach geändert

v3.2.20210126

* _FIX_: Quickfix wegen Sicherheitscheck bei Datenabholung

v3.1.20210116

* _NEU_: Funktion DateInfo liefert die Daten jetzt im JSON-Format
* _FIX_: Fehlerbehandlung komplett neu umgesetzt

v3.0.20210103

* _NEU_: Ermittlung der Ferien und Feiertage für DE, AT und CH
* _NEU_: Umstellung der Datenlieferung auf schulferien.org
* _FIX_: Name des Feiertages nicht korrekt gespeichert
* _FIX_: Vereinheitlichungen der Libs

v2.0.20200416

* _NEU_: Ermittlung der Arbeitstage im Monat
* _NEU_: Funktion DateInfo für manuelles Ermitteln der Daten für ein bestimmtes Datum
* _NEU_: Umstellung der Entwicklung auf Symcon StylePHP & Workflow actions

v1.2.20190813

* _NEU_: Anpassungen für Module Store
* _NEU_: Vereinheitlichungen, Umstellung auf Libs
* _NEU_: Lokalisierung (Englisch)

v1.1.20190501

* _FIX_: Name des Feiertages nicht korrekt gespeichert

v1.1.20190312

* _NEU_: Vereinheitlichungen, StyleCI uvm.

v1.0.20180505

* _FIX_: BugFix IPS 5.0

v1.0.20171230

* _NEU_: Initialversion

## Danksagung

Ich möchte mich für die Unterstützung bei der Entwicklung dieses Moduls bedanken bei  ...

* _KaiS_ : für den regen Austausch bei der allgemeinen Modulentwicklung
* _Nall-chan_ : für die initial Idee mit dem Modul _Schulferien_ <https://github.com/Nall-chan/IPSSchoolHolidays>
* _Nairda_ : für das Testen der Daten in der Schweiz
* _tomgr_ : für das Testen und Melden von Bugs

Vielen Dank für die hervorragende und tolle Arbeit!

## Entwickler

* Heiko Wilknitz ([@wilkware](https://github.com/wilkware))

## Spenden

Die Software ist für die nicht kommzerielle Nutzung kostenlos, Schenkungen als Unterstützung für den Entwickler bitte hier:

[![License](https://img.shields.io/badge/Einfach%20spenden%20mit-PayPal-blue.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

### Lizenz

[![Licence](https://licensebuttons.net/i/l/by-nc-sa/transparent/00/00/00/88x31-e.png)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
