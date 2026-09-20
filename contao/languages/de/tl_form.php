<?php

/**
 * Spam-Schutz
 */
$GLOBALS['TL_LANG']['tl_form']['enableTimeBasedSpamProtection']   = ['Zeitbasierten Spam-Schutz aktivieren', 'Prüft ob zwischen dem Laden und dem Absenden des Formulars eine Mindestzeit vergangen ist. Schützt gegen automatisierte Bot-Anfragen.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTime']                     = ['Mindestzeit (Sekunden)', 'Mindestzeit in Sekunden, die zwischen dem Laden und dem Absenden des Formulars vergehen muss.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTimeReference']            = [3 => '3 Sekunden', 5 => '5 Sekunden', 10 => '10 Sekunden', 15 => '15 Sekunden'];
$GLOBALS['TL_LANG']['tl_form']['form_spam_no_timestamp']          = 'Das Formular konnte nicht verarbeitet werden. Bitte laden Sie die Seite neu und versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_invalid_token']         = 'Ungültiges Formular-Token. Bitte laden Sie die Seite neu und versuchen Sie es erneut.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_too_fast']              = 'Das Formular wurde zu schnell abgesendet. Bitte warten Sie mindestens %s Sekunden.';
