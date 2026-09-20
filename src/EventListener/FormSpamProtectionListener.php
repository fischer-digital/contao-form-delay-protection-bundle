<?php

declare(strict_types=1);

namespace Tbo\FormDelayProtection\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Time-based spam protection for Contao forms.
 *
 * Injects an HMAC-signed hidden field with a server-side timestamp when
 * the form is rendered. On submission, the signature is verified and the
 * elapsed time is calculated. If the minimum delay has not passed, an
 * error is added and the form is NOT processed.
 *
 * Advantage: Completely session-independent – works with HTTP caching,
 * AJAX and without session cookies.
 */
class FormSpamProtectionListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
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
        if (!$this->isTimeProtectionEnabled($form)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return;
        }

        $formId = $form->formID ? 'auto_' . $form->formID : 'auto_form_' . $form->id;
        $token = $request->request->get('_form_load_token', '');

        if (empty($token)) {
            $form->addError(
                $this->translate(
                    'form_spam_no_timestamp',
                    'The form could not be processed. Please reload the page and try again.'
                )
            );

            return;
        }

        // Split token: timestamp.hmac
        $parts = explode('.', $token, 2);

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

        // Verify HMAC signature
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
        $elapsed = time() - (int) $timestamp;
        $minLoadTime = (int) ($form->minLoadTime ?: 5);

        if ($elapsed < $minLoadTime) {
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
