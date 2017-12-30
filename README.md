# Almanac

Dieses Modul bietet Kalenderinformationen für Arbeitstage, Feiertage, Schulferien und andere Kalenderdaten.

### Danksagung 

Dieses Modul basiert auf den Ideen und Modulen von ...
* _Nall-chan_ : Modul _Schulferien_ (https://github.com/Nall-chan/IPSSchoolHolidays) 

Vielen Dank für die hervorragende und tolle Arbeit! 

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Lizenz](#8-lizenz)

### 1. Funktionsumfang

Das Modul nutzt die von schulferien.eu (www.schulferien.eu) bereitgestellten Daten zur Anzeige der Feiertage und Schulferien
für das gewählte Bundesland.  
Darüber hinaus werden mittels der PHP Funktion "date" verschiedene Informationen für das aktuelle Datum ermittelt

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

### 2. Voraussetzungen

- IP-Symcon ab Version 4.x (getestet mit Version 4.4 auf RP3)

### 3. Software-Installation

Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconAlmanac` oder `git://github.com/Wilkware/IPSymconAlmanac.git`

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'Almanac'-Modul (Alias: Kalender, Schulferien, Feiertage) unter dem Hersteller '(Sonstige)' aufgeführt.

__Konfigurationsseite__:


Name               | Beschreibung
------------------ | ---------------------------------
Bundesland         | Auswahl des Bundesland für welchen man die Feiertage und Schulferien ermittelt haben möchte.
Basis URL          | Url zum Dienstanbieter für Feiertage und Schulferien, derzeit 'https://www.schulferien.eu/downloads/ical4.php'
Feiertage          | Status, ob Ermittlung der Feiertage erwünscht ist.
Schulferien        | Status, ob Ermittlung der Schulferien erwünscht ist.
Datumsfunktion     | Status, ob Informationen zum aktuellen Datum erwünscht sind.


### 5. Statusvariablen und Profile

Die Statusvariablen/Timer werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
Feiertag             | String    | Name des Feriertages oder 'kein Feiertag'
Ist Feiertag         | Boolean   | Ist aktueller Tag ein Feiertag?
Ferien               | String    | Name der Schulferien oder 'keine Ferien'
Ist Ferienzeit       | Boolean   | Fällt aktueller Tag in die Ferien?
Ist Schaltjahr       | Boolean   | Ist aktueller Jahr ein Schaltjahr?
Ist Sommerzeit       | Boolean   | Ist aktuell Sommerzeit aktiv?
Ist Wochenende       | Boolean   | Ist gerade Wochenende?
Kalenderwoche        | Integer   | Nummer der aktuelle Kalenderwoche
Tag  im Jahr         | Integer   | Welcher Tag des Jahres?
Tage im Monat        | Integer   | Wieviel Tage hat der aktuelle Monat?

Folgende Profile werden angelegt:

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
ALMANAC.Question     | Boolean   | FALSE = Nein / TRUE = Ja


### 6. WebFront

Man kann die Statusvariaben(Strings) direkt im WF verlinken.


### 7. PHP-Befehlsreferenz

`void ALMANAC_Update(int $InstanzID);`
Holt entsprechend der Konfiguration die gewählten Daten.
Die Funktion liefert keinerlei Rückgabewert.

Beispiel:
`ALMANAC_Update(12345);`

### 8. Lizenz

  [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  