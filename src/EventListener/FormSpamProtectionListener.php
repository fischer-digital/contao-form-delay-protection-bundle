<?php

declare(strict_types=1);

namespace Tbo\FormDelayProtection\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Time-based and pattern-based spam protection for Contao forms.
 *
 * Time-based protection:
 * Injects an HMAC-signed hidden field with a server-side timestamp when
 * the form is rendered. On submission, the signature is verified and the
 * elapsed time is calculated. If the minimum delay has not passed, an
 * error is added and the form is NOT processed.
 *
 * Pattern-based protection:
 * Checks the submitted values for typical spam patterns (consonant
 * gibberish, case jumble, Gmail dot trick).
 *
 * Silent drop:
 * Both checks can optionally drop a submission silently: the success
 * message is shown as normal, but no email is sent, no data is stored and
 * nothing is written to the session.
 *
 * Advantage: Completely session-independent – works with HTTP caching,
 * AJAX and without session cookies.
 */
class FormSpamProtectionListener
{
    /**
     * Hard time floor for the silent drop (in seconds).
     *
     * Submissions faster than this are considered definite bot traffic and
     * are silently dropped – independent of the configured minimum time.
     */
    private const SILENT_DROP_MAX_SECONDS = 3;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.secret%')]
        private readonly string $secret,
    ) {
    }

    /**
     * Injects an HMAC-signed hidden field with a timestamp into the form.
     *
     * The field value format is: timestamp.hmac
     * - timestamp: UNIX timestamp (seconds) at render time
     * - hmac: HMAC-SHA256 over timestamp + formId, signed with the kernel secret
     *
     * Always injected (GET + POST) so that the AJAX re-render after a
     * failed submission always contains a fresh token.
     *
     * IMPORTANT: The compileFormFields hook expects the $arrFields array to be
     * returned (no pass-by-reference!).
     */
    #[AsHook('compileFormFields')]
    public function onCompileFormFields(array $arrFields, string $formId, Form $form): array
    {
        if (!$this->isTimeProtectionEnabled($form)) {
            return $arrFields;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return $arrFields;
        }

        // Always inject the token – even on POST, so the AJAX re-render
        // contains a fresh token for subsequent submission attempts.
        $timestamp = (string) time();
        $hmac = $this->generateHmac($timestamp, $formId);

        // Append hidden field to the form (rendered in form_inline.html5)
        $form->Template->hidden .= sprintf(
            '<input type="hidden" name="_form_load_token" value="%s.%s">',
            $timestamp,
            $hmac,
        );

        return $arrFields;
    }

    /**
     * Checks on form submission whether the minimum delay has elapsed.
     *
     * Reads the HMAC-signed token from the submitted data, verifies the
     * signature and calculates the elapsed time.
     *
     * Runs BEFORE the actual data processing (email, database).
     */
    #[AsHook('prepareFormData')]
    public function onPrepareFormData(
        array &$arrSubmitted,
        array $arrLabels,
        array $arrFields,
        Form $form,
        array &$arrFiles,
    ): void {
        $timeProtectionEnabled = $this->isTimeProtectionEnabled($form);
        $regexProtectionEnabled = $this->isRegexProtectionEnabled($form);

        if (!$timeProtectionEnabled && !$regexProtectionEnabled) {
            return;
        }

        // Ensure our bundle's language file is loaded for $GLOBALS['TL_LANG']['tl_form']
        System::loadLanguageFile('tl_form');

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        // Pattern-based spam checks (do not require the token, so they also
        // work when the time-based protection is disabled)
        if ($regexProtectionEnabled && $this->matchesRegexSpam($arrSubmitted, $arrFields, $form)) {
            if ($this->isSilentDropEnabled($form)) {
                $this->suppressProcessing($form, 'regex_spam');

                return;
            }

            $form->addError(
                $this->translate(
                    'form_spam_suspicious_content',
                    'The form could not be processed. Please check your entries and try again.'
                )
            );

            return;
        }

        // From here on the time-based check requires the token
        if (!$timeProtectionEnabled) {
            return;
        }

        $formId = $form->formID ? 'auto_' . $form->formID : 'auto_form_' . $form->id;
        $rawToken = $request->request->get('_form_load_token');

        // Ensure the token is a string (not an array from _form_load_token[])
        if (!is_string($rawToken) || $rawToken === '') {
            $form->addError(
                $this->translate(
                    'form_spam_no_timestamp',
                    'The form could not be processed. Please reload the page and try again.'
                )
            );

            return;
        }

        // Reject oversized tokens (DoS prevention)
        // Format: "10-digit-timestamp.64-hex-chars" = max ~80 chars
        if (\strlen($rawToken) > 200) {
            $form->addError(
                $this->translate(
                    'form_spam_invalid_token',
                    'Invalid form token. Please reload the page and try again.'
                )
            );

            return;
        }

        // Reject tokens containing null bytes or non-printable characters
        if (str_contains($rawToken, "\0") || preg_match('/[^\x20-\x7E]/', $rawToken)) {
            $form->addError(
                $this->translate(
                    'form_spam_invalid_token',
                    'Invalid form token. Please reload the page and try again.'
                )
            );

            return;
        }

        // Split token: timestamp.hmac
        $parts = explode('.', $rawToken, 2);

        if (count($parts) !== 2 || !ctype_digit($parts[0])) {
            $form->addError(
                $this->translate(
                    'form_spam_invalid_token',
                    'Invalid form token. Please reload the page and try again.'
                )
            );

            return;
        }

        [$timestamp, $submittedHmac] = $parts;

        // Validate HMAC format: SHA-256 produces exactly 64 lowercase hex characters
        if (preg_match('/^[0-9a-f]{64}$/', $submittedHmac) !== 1) {
            $form->addError(
                $this->translate(
                    'form_spam_invalid_token',
                    'Invalid form token. Please reload the page and try again.'
                )
            );

            return;
        }

        // Validate timestamp is within a reasonable range
        $ts = (int) $timestamp;

        if ($ts < 0 || $ts > time() + 300) {
            $form->addError(
                $this->translate(
                    'form_spam_invalid_token',
                    'Invalid form token. Please reload the page and try again.'
                )
            );

            return;
        }

        // Verify HMAC signature (timing-safe comparison)
        if (!hash_equals($this->generateHmac($timestamp, $formId), $submittedHmac)) {
            $form->addError(
                $this->translate(
                    'form_spam_invalid_token',
                    'Invalid form token. Please reload the page and try again.'
                )
            );

            return;
        }

        // Calculate elapsed time
        $elapsed = time() - $ts;
        $minLoadTime = (int) ($form->minLoadTime ?: 5);

        if ($elapsed < $minLoadTime) {
            // Hard floor: submissions faster than 3 seconds are definite bot
            // traffic – silently drop them instead of showing an error
            if ($this->isSilentDropEnabled($form) && $elapsed < self::SILENT_DROP_MAX_SECONDS) {
                $this->suppressProcessing($form, 'submitted_too_fast');

                return;
            }

            $form->addError(
                sprintf(
                    $this->translate(
                        'form_spam_too_fast',
                        'The form was submitted too quickly. Please wait at least %s seconds.'
                    ),
                    $minLoadTime
                )
            );
        }
    }

    /**
     * Checks whether pattern-based spam protection is enabled for this form.
     */
    private function isRegexProtectionEnabled(Form $form): bool
    {
        return !empty($form->enableRegexSpamProtection);
    }

    /**
     * Checks whether detected spam should be silently dropped.
     */
    private function isSilentDropEnabled(Form $form): bool
    {
        return !empty($form->enableSilentDrop);
    }

    /**
     * Matches the submitted values against the spam patterns.
     *
     * 1. Consonant gibberish (5+ consecutive consonants) – single-line text
     *    fields only (name/street-like inputs), as German compound words in
     *    free text can contain long consonant clusters ("selbstständig").
     * 2. Case jumble (e.g. "aggZJZAK") – all textual values.
     * 3. Gmail dot trick – email-like values.
     *
     * Fields listed in the "regexSpamExcludeFields" option (comma-separated
     * field names) are skipped entirely.
     */
    private function matchesRegexSpam(array $arrSubmitted, array $arrFields, Form $form): bool
    {
        $excludedFields = $this->getRegexExcludedFields($form);

        foreach ($arrSubmitted as $name => $value) {
            if (in_array(strtolower((string) $name), $excludedFields, true)) {
                continue;
            }

            $type = $arrFields[$name]->type ?? null;

            foreach ($this->flattenValues($value) as $string) {
                // 1. Consonant gibberish (e.g. "xjkrtw")
                if ('text' === $type && preg_match('/[b-df-hj-np-tv-z]{5,}/i', $string)) {
                    return true;
                }

                // 2. Case jumble in the middle of a word (e.g. "aggZJZAK")
                if (preg_match('/[a-z]{2,}[A-Z]{2,}/', $string)) {
                    return true;
                }

                // 3. Gmail dot trick (3+ dots in the local part of a gmail.com address)
                if (('email' === $type || str_contains($string, '@')) && $this->isGmailDotTrick($string)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Recursively flattens a submitted value into a list of non-empty strings.
     *
     * @return list<string>
     */
    private function flattenValues(mixed $value): array
    {
        if (is_array($value)) {
            $result = [];

            foreach ($value as $item) {
                array_push($result, ...$this->flattenValues($item));
            }

            return $result;
        }

        return is_string($value) && '' !== $value ? [$value] : [];
    }

    /**
     * Returns the lowercase field names that are excluded from the regex checks.
     *
     * @return list<string>
     */
    private function getRegexExcludedFields(Form $form): array
    {
        $names = explode(',', (string) ($form->regexSpamExcludeFields ?? ''));

        return array_values(array_filter(array_map(
            static fn (string $name): string => strtolower(trim($name)),
            $names
        )));
    }

    /**
     * Detects the Gmail dot trick (3+ dots in the local part of a gmail.com address).
     */
    private function isGmailDotTrick(string $value): bool
    {
        $value = trim($value);

        if (!str_ends_with(strtolower($value), '@gmail.com')) {
            return false;
        }

        $localPart = explode('@', $value)[0];

        return substr_count($localPart, '.') >= 3;
    }

    /**
     * Silently drops the current submission.
     *
     * The success message/redirect is shown as normal, but no email is sent,
     * no data is stored in the database and nothing is written to the session.
     */
    private function suppressProcessing(Form $form, string $reason): void
    {
        $form->sendViaEmail = false;
        $form->storeValues = false;
        $form->storeSession = false;

        $this->logger->warning(sprintf(
            'Form "%s" (ID %s): submission silently dropped (reason: %s), no data was processed.',
            (string) $form->title,
            (string) $form->id,
            $reason
        ));
    }

    /**
     * Checks whether time-based spam protection is enabled for this form.
     */
    private function isTimeProtectionEnabled(Form $form): bool
    {
        return !empty($form->enableTimeBasedSpamProtection);
    }

    /**
     * Generates an HMAC-SHA256 over timestamp + form ID.
     */
    private function generateHmac(string $timestamp, string $formId): string
    {
        return hash_hmac('sha256', $timestamp . '|' . $formId, $this->secret);
    }

    /**
     * Returns a translated message or the given fallback string.
     */
    private function translate(string $key, string $fallback): string
    {
        return $GLOBALS['TL_LANG']['tl_form'][$key] ?? $fallback;
    }
}
