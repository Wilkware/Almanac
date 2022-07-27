# Almanac (Jahreskalender)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-6.0-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-5.1.20220706-orange.svg)](https://github.com/Wilkware/IPSymconAlmanac)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/IPSymconAlmanac/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/IPSymconAlmanac/actions)

Dieses Modul bietet jährliche Kalenderinformationen wie Feiertage, Schulferien und Festtage.  
Außerdem werden Informationen wie Arbeitstage im Monat, Schaltjahr, Jahreszeit oder ob Wochenende ist aktuell gehalten.  
Darüber hinaus kann man Geburtstage, Hochzeitstage und Todestage verwalten und sich täglich informieren lassen.  
Auch verschiedene astronomische Daten wie Mond- und Sonnenfinsternis oder die Daten der Mondphasen (Neumond, zunehmenden Mond, Vollmond und abnehmenden Mond) werden bereitgestellt.  
Ein Zitat des Tages rundet die Funktionalität des Modules ab.

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
Derzeit unterstützt das Modul auch eine Vielzahl verschiedenster religiöser und weltlicher Festtage (z.B. Valentinstag oder Kindertag).  
Als Gedächtnisstütze können die jährlichen Geburtstage, Hochzeitstage aber auch Todestage verwaltet werden und man
kann sich täglich informieren lassen ob ein Termin ansteht (Meldungsverwaltung oder via Webfront-Notification).  
Darüber hinaus werden mittels der PHP Funktion "date" verschiedene Informationen für das aktuelle Datum ermittelt.  
In Kombination mit den ermittelten Feiertagen werden auch die Arbeitstage im aktuellen Monat bereitgestellt.  
Spezielle astronomische Ereignisse wie Mond- oder Sonnenfinsternis und das Datum der 4 verschiedenen Mondphasen für das nächste Datum wird ermittelt.
Aber auch ein "Zitat des Tages" kann abgerufen werden.

Folgende Informationen werden ermittelt:

* Sind Ferien und welche
* Feiertag oder nicht und wie heißt er
* Festag oder nicht und wie heißt er
* Hat jemand Geburtstag, Hochzeitstag oder Todestag
* Der Tag des Jahres
* Anzahl Tage im Monat
* Arbeitstage im Monat
* Schaltjahr oder nicht
* Sommerzeit oder nicht
* Wochenende oder nicht
* Nummer der Kalenderwoche
* Wochentag (ISO-8601)
* Jahreszeit (Frühling, Sommer, Herbst und Winter)
* Ist eine Mond- oder Sonnenfinsternis
* Tritt eine Mondphasen (Neumond, zunehmenden Mond, Vollmond oder abnehmenden Mond) ein
* Zitat des Tages (Spruch und Autor)

All diese Information können auch über die Methode [ALMANAC_DateInfo](#7-php-befehlsreferenz) als Array abgeholt werden.

Folgende Informationen stehen als key => value Paare zur Verfügung:

Index                 | Typ     | Beschreibung
--------------------- | ------- | ----------------
IsSummer              | bool    | TRUE, wenn Sommerzeit ist
IsLeapYear            | bool    | TRUE, wenn Schaltjahr ist
IsWeekend             | bool    | TRUE, wenn Wochenende ist (SA-SO)
Weekday               | int     | Wochentag (1=Montag ... 7=Sonntag)
WeekNumber            | int     | Kalenderwochennummer
DaysInMonth           | int     | Anzahl Tage im Monat
DayOfYear             | int     | Tag im Jahr (1-366)
Season                | string  | Name der Jahreszeit ("Spring", "Summer", "Fall" oder "Winter")
Festive               | string  | Name des Festtags, oder "Kein Festtag"
IsFestive             | bool    | TRUE, wenn Festtag ist
WorkingDays           | int     | Arbeitstage im Monat
Holiday               | string  | Name des Feiertags, oder "Kein Feiertag"
IsHoliday             | bool    | TRUE, wenn Feiertag ist
Vacation              | string  | Name der Schulferien, oder "Keine Ferien"
IsVacation            | bool    | TRUE, wenn Schulferienzeit ist
IsBirthday            | bool    | TRUE, wenn Geburtstag(e) ansteht
Birthday              | array   | LEER, oder Feld mit Datum, Jahrestag und Name
IsWeddingday          | bool    | TRUE, wenn Hochzeitstag(e) ansteht
Weddingday            | array   | LEER, oder Feld mit Datum, Jahrestag und Name
IsDeathday            | bool    | TRUE, wenn Todestag(e) ansteht
Deathday              | array   | LEER, oder Feld mit Datum, Jahrestag und Name
IsEclipse             | bool    | TRUE, wenn Mond- oder Sonnenfinsternis ist
Eclipse               | array   | Feld mit Name, Datum, Uhrzeit des nächsten Ereignisses (LEER, wenn im aktuellen Jahr kein Ereignis mehr ist)
IsMoonphase           | bool    | TRUE, wenn Mondphase ist
Moonphase             | array   | Feld mit Name, Datum, Uhrzeit des nächsten Ereignisses (LEER, wenn im aktuellen Jahr kein Ereignis mehr ist)
QuoteOfTheDay         | array   | Feld mit Zitat und Autor

### 2. Voraussetzungen

* IP-Symcon ab Version 6.0

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
Text in Variable schreiben    | Auswahl ob Nachricht in Variable geschrieben werden soll
Texttrennzeichen/Zeilenumbruch| Trennzeichen bei mehreren Ereignissen

> Hochzeitstage ...

Name                          | Beschreibung
----------------------------- | ---------------------------------
Termine                       | Eingabe Heiratstermins (Tag.Monat.Jahr) und den dazugehörigen Namen
Nachricht ans Webfront senden | Auswahl ob Push-Nachricht gesendet werden soll oder nicht (Ja/Nein)
Nachricht Sendezeit           | Uhrzeit wann täglich die Nachricht gesendet weden soll
Meldung an Anzeige senden     | Auswahl ob Eintrag in die Meldungsverwaltung erfolgen soll oder nicht (Ja/Nein)
Lebensdauer der Nachricht     | Wie lange so die Meldung angezeigt werden?
Format der Textmitteilung     | Frei wählbares Format der zu sendenden Nachricht/Meldung
Text in Variable schreiben    | Auswahl ob Nachricht in Variable geschrieben werden soll
Texttrennzeichen/Zeilenumbruch| Trennzeichen bei mehreren Ereignissen

> Todestage ...

Name                          | Beschreibung
----------------------------- | ---------------------------------
Termine                       | Eingabe Sterbetag (Tag.Monat.Jahr) und den dazugehörigen Namen
Nachricht ans Webfront senden | Auswahl ob Push-Nachricht gesendet werden soll oder nicht (Ja/Nein)
Nachricht Sendezeit           | Uhrzeit wann täglich die Nachricht gesendet weden soll
Meldung an Anzeige senden     | Auswahl ob Eintrag in die Meldungsverwaltung erfolgen soll oder nicht (Ja/Nein)
Lebensdauer der Nachricht     | Wie lange so die Meldung angezeigt werden?
Format der Textmitteilung     | Frei wählbares Format der zu sendenden Nachricht/Meldung
Text in Variable schreiben    | Auswahl ob Nachricht in Variable geschrieben werden soll
Texttrennzeichen/Zeilenumbruch| Trennzeichen bei mehreren Ereignissen

> Verschiednes ...

Name                                                   | Beschreibung
------------------------------------------------------ | ----------------------------------------------------------
Textausgabeformat für Mond- und Sonnenfinsternisse     | Frei wählbares Format für die Ereignisausgabe
Textausgabeformat für Mondphasen                       | Frei wählbares Format für die Ereignisausgabe
Textausgabeformat für Zitat des Tages                  | Frei wählbares Format für die Zitatsausgabe

> Erweiterte Einstellungen ...

Name                                      | Beschreibung
----------------------------------------- | ---------------------------------
Feirtage ermitteln                        | Status, ob Ermittlung der Feiertage erwünscht ist
Schulferien ermitteln                     | Status, ob Ermittlung der Schulferien erwünscht ist
Festtage ermitteln                        | Status, ob Ermittlung der Festtage erwünscht ist
Geburtstage ermitteln                     | Status, ob Geburtstage ausgewertet werden sollen
Hochzeitstage ermitteln                   | Status, ob Hochzeitstage ausgewertet werden sollen
Todestage ermitteln                       | Status, ob Todesstage ausgewertet werden sollen
Finsternisse ermitteln                    | Status, ob Ermittlung von Mond- oder Sonnenfinsternisse erwünscht ist
Mondphasen ermitteln                      | Status, ob Ermittlung von Mondphasen erwünscht ist
Zitat des Tages ermitteln                 | Status, ob Zitat des Tages ausgegeben werden soll
Information zum aktuellen Datum ermitteln | Status, ob Informationen zum aktuellen Datum erwünscht sind.
WebFront Instanz                          | ID des Webfronts, an welches die Push-Nachrichten für Geburts-, Hochzeits- und Todestage gesendet werden soll
Meldsungsskript                           | Skript ID des Meldungsverwaltungsskripts, weiterführende Infos im Forum: [Meldungsanzeige im Webfront](https://community.symcon.de/t/meldungsanzeige-im-webfront/23473)

Aktionsbereich:

> Import & Export von ...

Aktion         | Beschreibung
-------------- | ------------------------------------------------------------
GEBURTSTAGE    | Öffnet Popup für die Möglichkeit zum Import/Export/Leeren der Geburtstagsliste als CSV Datei (geburtstage.csv)
HOCHZEITSTAGE  | Öffnet Popup für die Möglichkeit zum Import/Export/Leeren der Hochzeitsliste als CSV Datei (hochzeitstage.csv)
TODESTAGE      | Öffnet Popup für die Möglichkeit zum Import/Export/Leeren der Sterbeliste als CSV Datei (todestage.csv)

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
Ist Geburtstag?      | Boolean   | Ist am aktuellen Tag ein Geburtstag?
Ist Hochzeitstag?    | Boolean   | Ist am aktuellen Tag ein Hochzeitstag?
Ist Todestag?        | Boolean   | Ist am aktuellen Tag ein Todestag?
Feiertag             | String    | Name des Feriertages oder 'Kein Feiertag'
Ferien               | String    | Name der Schulferien oder 'Keine Ferien'
Festtag              | String    | Name des Festtages oder 'Kein Festtag'
Geburtstag           | String    | Formatierte Ausgabe des Geburtstages oder leer
Hochzeitstag         | String    | Formatierte Ausgabe des Hochzeitstages oder leer
Todestag             | String    | Formatierte Ausgabe des Todestages oder leer
Kalenderwoche        | Integer   | Nummer der aktuelle Kalenderwoche
Tage im Monat        | Integer   | Wieviel Tage hat der aktuelle Monat?
Tag im Jahr          | Integer   | Welcher Tag des Jahres?
Arbeitstage im Monat | Integer   | Wieviel Arbeitstage hat der Monat des gewählten Bundeslandes?
Jahreszeit           | String    | "Frühling", "Sommer", "Herbst" oder "Winter"

Folgende Profile werden angelegt:

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
ALMANAC.Question     | Boolean   | FALSE = Nein(No) / TRUE = Ja(Yes)
ALMANAC.Sesaon       | String    | Winter(Winter), Frühling(Spring), Herbst(Fall), Sommer(Summer)

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
> "Weekday": 1,  
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
> "IsVacation": false,  
> "IsBirthday": true,  
> "Birthday": [{"date": 14.2.1970, "years": 51, "name": "Valentin Tag"}],  
> "IsWeddingday": false,  
> "Weddingday": [],  
> "IsDeathday]: false,  
> "Deathday": []  
> "IsEclipse": true,  
> "Eclipse": [{"name": "Partielle Sonnenfinsternis", "date": "30.04.2022", "time": "22:42:00"}],  
> "IsMoonphase": true,  
> "Moonphase": [{"name": "Neumond", "date": "30.04.2022", "time": "22:34:00"}],  
> "QuoteOfTheDay": [{"quote": "Bist du wütend, zähl bis vier, hilft das nicht, dann explodier.", "author": "Wilhelm Busch"}]  
}  

### 8. Versionshistorie

v5.1.20220706

* _NEU_: Wochentag (nach ISO-8601) aufgenommen
* _FIX_: Dokumentation vereinheitlicht

v5.0.20220101

* _NEU_: Kompatibilität auf IPS 6.0 hoch gesetzt
* _NEU_: Update auf Version 3 vom PHP Coding Standards Fixer
* _NEU_: String-Profile aufgenommen (z.B. für Jahreszeit)
* _NEU_: Bibliotheks- bzw. Modulinfos vereinheitlicht
* _NEU_: Englische Übersetzungen aufgenommen bzw. vervollständigt
* _NEU_: Konfigurationsdialog überarbeitet (v6 Möglichkeiten genutzt)
* _NEU_: Mondphasen integriert
* _NEU_: Mond- und Sonnenfinsternisse integriert
* _NEU_: Zitat des Tages integriert
* _FIX_: Fehler in Webhook Helper korrigiert
* _FIX_: Fehler bei der Ausgabe der Jahreszeit korrigiert
* _FIX_: Leere JSON-Liste korrekt initialisiert
* _FIX_: Ferienermittelung bei Jahresanfang korrigiert

v4.3.20210527

* _FIX_: Fehler beim Auswerten der erweiterten Einstellungen gefixt
* _NEU_: Debugmeldungen erweitert und vereinheitlicht

v4.2.20210406

* _FIX_: Mitternachts-Timer hat vereinzelt zu zeitig ausgelöst

v4.1.20210307

* _FIX_: Feiertage und Schulferien waren 1 Tag zu lang

v4.0.20210214

* _NEU_: Eigener Webservice (JSON-API) für Ferien und Feiertage in DE, AT und CH (aktuell 2015 - 2022)
* _NEU_: Ermittlung von verschiedensten religiösen und weltlichen Festtagen
* _NEU_: Ermittlung der aktuellen Jahreszeit ("Frühling", "Sommer", "Herbst" oder "Winter")
* _NEU_: Verwaltung und Meldung von Geburtstagen (Liste)
* _NEU_: Verwaltung und Meldung von Hochzeitstagen (Liste)
* _NEU_: Verwaltung und Meldung von Todesstagen (Liste)
* _NEU_: Import & Export Funktionalität für Geburts-, Hochzeits- und Todestage
* _NEU_: Ferienzeitraum kann jetzt mit Ferienname ausgegeben werden
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

Ich möchte mich für die Unterstützung bei der Entwicklung dieses Moduls bedanken bei ...

* _KaiS_ : für den regen Austausch bei der allgemeinen Modulentwicklung
* _Nall-chan_ : für die initial Idee mit dem Modul [_Schulferien_](https://github.com/Nall-chan/IPSSchoolHolidays)
* _Nairda_ : für das Testen der Daten in der Schweiz
* _ralf_, _timloe_ : für die Anregung und Austausch für eine IPSView-konforme Formatierung
* _Attain_. _bumaas_, _tomgr_ : für das generelle Testen und Melden von Bugs
* _yansoph_ : für das Testen und schnelle Feedback
* _Dr.Niels_: für den Pull Request zum Initialisieren der leeren JSON-Listen

Vielen Dank für die hervorragende und tolle Arbeit!

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der IP-Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommzerielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
