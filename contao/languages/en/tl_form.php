<?php

/**
 * Spam protection
 */
$GLOBALS['TL_LANG']['tl_form']['enableTimeBasedSpamProtection']   = ['Enable time-based spam protection', 'Checks whether a minimum time has elapsed between loading and submitting the form. Protects against automated bot requests.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTime']                     = ['Minimum time (seconds)', 'Minimum time in seconds that must elapse between loading and submitting the form. Default: 5 seconds.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTimeReference']            = [3 => '3 seconds', 5 => '5 seconds', 10 => '10 seconds', 15 => '15 seconds'];
$GLOBALS['TL_LANG']['tl_form']['enableRegexSpamProtection']        = ['Enable regex spam protection', 'Checks the input for typical spam patterns: (1) five or more consecutive consonants such as "xjkrtw" and (2) three or more identical consecutive letters such as "rrrttzr" (single-line text fields only), (3) unusual upper/lowercase mixing within a word such as "aggZJZAK" (all textual values) and (4) the dot trick with three or more dots in the local part of an email address such as "j.o.h.n.doe@gmail.com".'];
$GLOBALS['TL_LANG']['tl_form']['regexSpamExcludeFields']            = ['Exceptions (field names)', 'Comma-separated list of field names (name/alias of the form fields) that are excluded from the regex checks.'];
$GLOBALS['TL_LANG']['tl_form']['enableSilentDrop']                = ['Silently drop spam', 'Shows the regular success message when spam is detected (regex match or submission within less than 3 seconds), but does not send or store the data. This way bots do not get any feedback that they have been detected.'];
$GLOBALS['TL_LANG']['tl_form']['form_spam_no_timestamp']          = 'The form could not be processed. Please reload the page and try again.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_invalid_token']         = 'Invalid form token. Please reload the page and try again.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_too_fast']              = 'The form was submitted too quickly. Please wait at least %s seconds.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_suspicious_content']    = 'The form could not be processed. Please check your entries and try again.';
