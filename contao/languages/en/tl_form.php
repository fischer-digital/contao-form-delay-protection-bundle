<?php

/**
 * Spam protection
 */
$GLOBALS['TL_LANG']['tl_form']['enableTimeBasedSpamProtection']   = ['Enable time-based spam protection', 'Checks whether a minimum time has elapsed between loading and submitting the form. Protects against automated bot requests.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTime']                     = ['Minimum time (seconds)', 'Minimum time in seconds that must elapse between loading and submitting the form.'];
$GLOBALS['TL_LANG']['tl_form']['minLoadTimeReference']            = [3 => '3 seconds', 5 => '5 seconds', 10 => '10 seconds', 15 => '15 seconds'];
$GLOBALS['TL_LANG']['tl_form']['form_spam_no_timestamp']          = 'The form could not be processed. Please reload the page and try again.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_invalid_token']         = 'Invalid form token. Please reload the page and try again.';
$GLOBALS['TL_LANG']['tl_form']['form_spam_too_fast']              = 'The form was submitted too quickly. Please wait at least %s seconds.';
