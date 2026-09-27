<?php

/**
 * Spam protection
 */
$GLOBALS['TL_LANG']['tl_form']['enableTimeBasedSpamProtection']   = ['Enable time-based spam protection', 'Checks whether a minimum time has elapsed between loading and submitting the form. Protects against automated bot requests.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTime']                     = ['Minimum time (seconds)', 'Minimum time in seconds that must elapse between loading and submitting the form. If the form is submitted faster, an error message is shown ("The form was submitted too quickly"). Default: 5 seconds.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTimeReference']            = [3 => '3 seconds', 5 => '5 seconds', 10 => '10 seconds', 15 => '15 seconds'];
$GLOBALS['TL_LANG']['tl_form']['enableRegexSpamProtection']        = ['Enable regex spam protection', 'Checks the input for typical spam patterns: (1) five or more consecutive consonants such as "xjkrtw" and (2) three or more identical consecutive letters such as "rrrttzr" (single-line text fields only), (3) unusual upper/lowercase mixing within a word such as "aggZJZAK" (all textual values) and (4) the dot trick with three or more dots in the local part of an email address such as "j.o.h.n.doe@gmail.com".'];
$GLOBALS['TL_LANG']['tl_form']['regexSpamExcludeFields']            = ['Exceptions (field names)', 'Comma-separated list of field names (name/alias of the form fields) that are excluded from the regex checks.'];
$GLOBALS['TL_LANG']['tl_form']['enableSilentDrop']                = ['Silent drop regex', 'Shows the regular success message when a submission matches one of the regex spam patterns, but does not send or store the data. If disabled, the sender is shown an error message instead. This way bots do not get any feedback that they have been detected. Sending via the Notification Center is suppressed as well.'];
$GLOBALS['TL_LANG']['tl_form']['silentDropTime']                 = ['Silent drop time', 'Silently drops submissions that are sent faster than the selected time (success message, but nothing is sent or stored). "Disabled" turns this rule off. Recommended: choose a value below the minimum time. Sending via the Notification Center is suppressed as well.'];
$GLOBALS['TL_LANG']['tl_form']['silentDropTimeReference']        = ['' => 'disabled', 3 => '< 3 seconds', 5 => '< 5 seconds', 10 => '< 10 seconds'];
$GLOBALS['TL_LANG']['tl_form']['enableSilentDropSysLog']         = ['Log silent drop messages to the system log', 'Records every silent drop including the submitted form data (as JSON) in the backend system log.'];
$GLOBALS['TL_LANG']['tl_form']['form_spam_no_timestamp']          = 'The form could not be processed. Please reload the page and try again.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_invalid_token']         = 'Invalid form token. Please reload the page and try again.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_too_fast']              = 'The form was submitted too quickly. Please wait at least %s seconds.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_suspicious_content']    = 'The form could not be processed. Please check your entries and try again.';
