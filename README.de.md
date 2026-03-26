ℍ&ℍwt - HuH Extensions for webtrees - Multi-Treeview
============================

[![Latest Release](https://img.shields.io/github/v/release/huhwt/huhwt-mtv)][1]
[![webtrees major version](https://img.shields.io/badge/webtrees-v2.2-green)][2]
[![Downloads](https://img.shields.io/github/downloads/huhwt/huhwt-mtv/total)]()

Erweiterungen für Webtrees zur Prüfung und Anzeige von Duplikaten und anderen Inkonsistenzen in der Datenbank.

Dies ist ein webtrees 2.2 Modul - kann nicht mit webtrees 2.1 benutzt werden.

Für Systeme mit webtrees 2.1 bitte das letzte Release aus dem huhwt-mtv Branch 2.1 verwenden.

Hinweis:
~~~
   Dieses Modul muss in einer PHP 8.3(aufwärts) System-Umgebung betrieben werden.
~~~

Einführung
-----------

Wenn man ein paar Jahre in einem der großen Genealogie-Dienste gearbeitet hat, ist die Chance groß, dass Sie durch die Nutzung von Abgleichsdiensten auch Duplikate erhalten, da die Qualität der Daten manchmal recht fragwürdig ist.

Dann ist es praktisch eine Funktion zu haben, um die Daten mit visuellen Hilfsmitteln nicht nur für jeden Abgleich einzeln, sondern auf einem Bildschirm gleichzeitig zu prüfen.

Die Abgleichliste in der 'Duplikate'-Ansicht für 'Personen' wird um einen Eintrag 'Interaktiver Vergleich' erweitert, der ein 'Interaktives Sanduhr-Diagramm' für jedes Individuum zusammen auf dem Bildschirm anzeigt. Dies geschieht auch wenn mehr als 2 Personen von der Funktion erfasst werden.

Beschreibung des Verfahrens
---------------------------

Duplikate werden über den Abgleich von Ereignissen zu Personen ermittelt. Beim Import des GEDcom in Webtrees werden die Dateninhalte auf diverse Tabellen verteilt. Ereignisse sind im GEDcom durch spezielle TAGs gekennzeichnet, diesen sind jeweils ein Ereignisdatum zugeordnet. In Webtrees werden diese Inhalte in der Tabelle wt_dates abgelegt, dem Datum wird die TAG-Kennung des Ereignisses als d_fact beigegeben. Personen haben Namen, diese werden in Webtrees in der Tabelle wt_name abgelegt. In beiden Tabellen stehen zu jedem Eintrag auch die XREFs, so dass differenziert werden kann, welcher Person-ID der Eintrag zugerechnet werden soll. Der Abgleich erfolgt durch Analyse, welche Ereignisse mit gleichem Datum bei welchen Personen mit gleichem Namen stattgefunden haben, diese werden dann als potentielle Duplikate ausgegeben.

Und hier wird es schwierig. Jedes beliebige Event mit Datum ist in wt_dates gelistet. Und als Eintrag in wt_name wird nicht nur das offizielle NAME-TAG übernommen, sondern auch TAGs wie _MARNM oder _AKA, welche als legacy oder individuell deklariert sind. Die Inhalte dieser TAGs sind nicht so signifikant wie der Inhalt des NAME-TAG - da steht ja der komplette Personenname mit allen Bestandteilen drin. Wenn also alle beliebigen Ereignisse mit nicht differenzierten Namen in Bezug gebracht werden, ist die Wahrscheinlichkeit eher hoch, dass im Ergebnis Ungereimtheiten auftreten - Garbage in, Garbage out.

Ein gewisse Schärfung des Ergebnisses hatte ich durch eine zusätzliche Klausel in der grundlegenden Abfrage im Modul 'app/Services/AdminService.php' erreicht. Durch Einschränkung der Abfrage auf solche Einträge, welche nur das NAME-TAG enthalten, wurden es weniger Duplikat-Meldungen. Eine weitere Verringerung ergab sich, wenn nur relevante Events gefiltert werden, das wären BIRT, CHR, BAPM, DEAT, BURI als Inhalte des Feldes d_fact in wt_dates.

Damit man nicht in den Code des Webtrees-Kern eingreifen muss, sind diese Optionen jetzt als eigene Funktion ausgegliedert, werden also nicht mehr zwangsläufig durch ein Webtrees-Update überschrieben. Man aktiviert sie über "Einstellungen" in der Verwaltung-Alle Module-Übersicht. Sind sie inaktiv, läuft weiterhin die originale Webtrees-Funktion, ist eine oder beide aktiv, wird die Abfrage entsprechend angepasst ausgeführt.

In den Einstellungen lassen sich die für den Abgleich relevanten Datums-Referenzen gezielt festlegen, bis hin zum kompletten Unterbleiben, dann wird nur auf übereinstimmende Namen geprüft. Damit lassen sich auch Situationen ermitteln, in denen Personen tatsächlich mehrfach im Stammbaum vorhanden sind, es bei den Daten aber unterschiedliche Werte gibt - Beispiel: Eine Person wurde mehrfach aus unterschiedlichen Quellen übernommen - Eintrag A Geburts- und Taufdatum unterschiedlich, Eintrag B (fälschlicherweise) Geburts- und Taufdatum mit gleichem Wert belegt, alle anderen Angaben gleich ... es handelt sich tatsächlich um die gleiche Person. Der Abgleich über Name und Daten erkennt diese Situation nicht.

Interaktives Sanduhr-Diagramm
-----------------------------

Für jede der als potentielle Duplikate eingeschätzte Personen wird eine eigene Ansicht erzeugt, analog der Webtrees-Kernfunktion 'Interaktives Sanduhr-Diagramm' - also die jeweilige Person mit Partner(n) in der Mitte und je bis zu 4 Generationen an Nachfahren nach links und Vorfahren nach rechts angeordnet. Die Ansicht ist gegenüber der Kernfunktion eingeschränkt, so wird sie zum Beispiel bei Klick in die Ansicht nicht automatisch erweitert.

Die Personen mit ihren Partnern werden in einer Box angezeigt mit ihrem Namen (ergänzt um die Kennung) und Lebensspanne. Ein Klick in diese Box ergänzt die Informationen um die konkreten Einträge zu Geburt und Tod, fallweise ergänzt um Ehe und Scheidung. In der erweiterten Box steht neben dem Namen nun auch ein Icon. Ein Klick auf dieses Icon öffnet ein neues eigenes origäres Interaktives Sanduhr-Diagramm für die jeweilige Person in einem neuen Browser-Tab.

Ein weiterer Klick in die erweiterte Box setzt sie wieder auf den Ausgangzustand zurück.

##### Hinweise:
##### Falls das Erweiterungs-Modul ['huhwt-xtv'](https://github.com/huhwt/huhwt-xtv) installiert/aktiviert ist, wird anstelle des Webtrees-Moduls 'Interaktives Sanduhr-Diagramm' das 'ℍ Interaktives Sanduhr-Diagramm XT' aufgerufen.
##### Falls das Erweiterungs-Modul ['huhwt-cce'](https://github.com/huhwt/huhwt-cce) installiert/aktiviert ist, kann man die angezeigten Personen in den Sammelbehälter 'ℍ Sammelbehälter' übernehmen.

Installation und Upgrading
--------------------------

... auf die übliche Art und Weise: Laden Sie die Zip-Datei herunter, entpacken Sie sie in das modules_v4-Verzeichnis, und das war's. Man sollte die vorhandene Version vorher komplett entfernen.

Danksagung
--------------------------

Fürs Test, Anregungen und Kritik besonderen Dank an Hermann Harthentaler.

Danke für die Übersetzung ins Niederländische an TheDutchJewel.

Danke für die Übersetzungen ins Catalanische und Spanische an BernatBanyuls.

Development
-------------------------

[TODO]

...

Bugs and feature requests
-------------------------
If you experience any bugs or have a feature request for this theme you can [create a new issue][3].

[1]: https://github.com/huhwt/huhwt-mtv/releases/latest
[2]: https://webtrees.net/download
[3]: https://github.com/huhwt/huhwt-mtv/issues?state=open