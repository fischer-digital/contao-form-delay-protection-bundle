<?php

/**
 * Spam-Schutz
 */
$GLOBALS['TL_LANG']['tl_form']['enableTimeBasedSpamProtection']   = ['Zeitbasierten Spam-Schutz aktivieren', 'Prüft ob zwischen dem Laden und dem Absenden des Formulars eine Mindestzeit vergangen ist. Schützt gegen automatisierte Bot-Anfragen.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTime']                     = ['Mindestzeit (Sekunden)', 'Mindestzeit in Sekunden, die zwischen dem Laden und dem Absenden des Formulars vergehen muss. Wird sie unterschritten, zeigt das Formular eine Fehlermeldung („Das Formular wurde zu schnell abgesendet“). Standard: 5 Sekunden.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTimeReference']            = [3 => '3 Sekunden', 5 => '5 Sekunden', 10 => '10 Sekunden', 15 => '15 Sekunden'];
$GLOBALS['TL_LANG']['tl_form']['enableRegexSpamProtection']        = ['Regex-Spam-Schutz aktivieren', 'Prüft die Eingaben auf typische Spam-Muster: (1) fünf oder mehr aufeinanderfolgende Konsonanten wie z. B. "xjkrtw" und (2) drei oder mehr identische Buchstaben in Folge wie z. B. "rrrttzr" (jeweils nur einzeilige Textfelder), (3) ungewöhnliche Groß-/Kleinschreibung mitten im Wort wie z. B. "aggZJZAK" (alle Textwerte) sowie (4) den Punkt-Trick mit drei oder mehr Punkten im lokalen Teil einer E-Mail-Adresse wie z. B. "j.o.h.n.doe@gmail.com".'];
$GLOBALS['TL_LANG']['tl_form']['regexSpamExcludeFields']            = ['Ausnahmen (Feldnamen)', 'Kommagetrennte Liste von Feldnamen (Name/Alias der Formularfelder), die von den Regex-Prüfungen ausgenommen werden.'];
$GLOBALS['TL_LANG']['tl_form']['enableSilentDrop']                = ['Silent Drop Regex', 'Zeigt bei einem Treffer der Regex-Spam-Prüfung normal die Erfolgsmeldung an, versendet und speichert die Daten aber nicht. Ist die Option deaktiviert, wird der Absender stattdessen mit einer Fehlermeldung abgewiesen. So erhalten Bots keine Rückmeldung darüber, dass sie aufgeflogen sind. Auch der Versand über das Notification Center wird unterdrückt.'];
$GLOBALS['TL_LANG']['tl_form']['silentDropTime']                 = ['Silent Drop Time', 'Verwirft Einreichungen, die schneller als die gewählte Zeit abgesendet werden, stillschweigend (Erfolgsmeldung, aber kein Versand und keine Speicherung). „Deaktiviert“ schaltet diese Regel ab. Empfehlung: einen Wert kleiner als die Mindestzeit wählen. Auch der Versand über das Notification Center wird unterdrückt.'];
$GLOBALS['TL_LANG']['tl_form']['silentDropTimeReference']        = ['' => 'deaktiviert', 3 => '< 3 Sekunden', 5 => '< 5 Sekunden', 10 => '< 10 Sekunden'];
$GLOBALS['TL_LANG']['tl_form']['enableSilentDropSysLog']         = ['Silent-Drop-Meldung ins System-Protokoll', 'Protokolliert jede stillschweigende Verwerfung inklusive der übermittelten Formulardaten (als JSON) im System-Protokoll des Backends.'];
$GLOBALS['TL_LANG']['tl_form']['form_spam_no_timestamp']          = 'Das Formular konnte nicht verarbeitet werden. Bitte laden Sie die Seite neu und versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_invalid_token']         = 'Ungültiges Formular-Token. Bitte laden Sie die Seite neu und versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_too_fast']              = 'Das Formular wurde zu schnell abgesendet. Bitte warten Sie mindestens %s Sekunden.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_suspicious_content']    = 'Das Formular konnte nicht verarbeitet werden. Bitte überprüfen Sie Ihre Eingaben und versuchen Sie es erneut.';
