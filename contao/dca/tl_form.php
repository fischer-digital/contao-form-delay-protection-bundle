<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\FormModel;
use Contao\Input;

/**
 * Extends the tl_form DCA with spam protection fields.
 *
 * NOTE: The fields intentionally do NOT have a "sql" definition – since
 * Contao 5.7 they are stored as virtual fields in the shared "jsonData"
 * column of tl_form (see Contao's VirtualFieldsMappingListener).
 */

// --- Fields ---

$GLOBALS['TL_DCA']['tl_form']['fields']['enableTimeBasedSpamProtection'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['enableTimeBasedSpamProtection'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['minLoadTime'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_form']['minLoadTime'],
    'exclude'       => true,
    'inputType'     => 'select',
    'options'       => [3, 5, 10, 15],
    'reference'     => &$GLOBALS['TL_LANG']['tl_form']['minLoadTimeReference'],
    'eval'          => ['tl_class' => 'w50'],
    'load_callback' => [
        // Default value (there is no column default for virtual fields)
        static fn ($value) => $value ?: '5',
    ],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['silentDropTime'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['silentDropTime'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['', 3, 5, 10],
    'reference' => &$GLOBALS['TL_LANG']['tl_form']['silentDropTimeReference'],
    'eval'      => ['tl_class' => 'w50'],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['enableRegexSpamProtection'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['enableRegexSpamProtection'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['regexSpamExcludeFields'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['regexSpamExcludeFields'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['enableSilentDrop'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['enableSilentDrop'],
    'exclude'   => true,
    'inputType' => 'checkbox',
];

$GLOBALS['TL_DCA']['tl_form']['fields']['enableSilentDropSysLog'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['enableSilentDropSysLog'],
    'exclude'   => true,
    'inputType' => 'checkbox',
];

// --- Selector + Subpalette ---

$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'enableTimeBasedSpamProtection';
$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'enableRegexSpamProtection';
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['enableTimeBasedSpamProtection'] = 'minLoadTime,silentDropTime';
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['enableRegexSpamProtection'] = 'regexSpamExcludeFields,enableSilentDrop';

// --- Append fields to end of config_legend ---

PaletteManipulator::create()
    ->addField(['enableTimeBasedSpamProtection', 'enableRegexSpamProtection'], 'storeSession', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_form');

// The system log checkbox is shared by both silent drop variants, so it must
// be shown when at least one spam protection is enabled. Contao palettes
// cannot express an "or" condition, hence the palette is adjusted dynamically
// on load (the toggled state of submitOnChange reloads is respected).
$GLOBALS['TL_DCA']['tl_form']['config']['onload_callback'][] = static function ($dc): void {
    $timeEnabled = false;
    $regexEnabled = false;

    if (($id = (int) Input::get('id')) > 0 && null !== ($formModel = FormModel::findByPk($id))) {
        $timeEnabled = !empty($formModel->enableTimeBasedSpamProtection);
        $regexEnabled = !empty($formModel->enableRegexSpamProtection);
    }

    // Overwrite the state with the submitted values (see Contao's PaletteBuilder)
    if ('tl_form' === (string) Input::post('FORM_SUBMIT')) {
        $postTime = Input::post('enableTimeBasedSpamProtection');
        $postRegex = Input::post('enableRegexSpamProtection');

        if (null !== $postTime) {
            $timeEnabled = '' !== (string) $postTime && '0' !== (string) $postTime;
        }

        if (null !== $postRegex) {
            $regexEnabled = '' !== (string) $postRegex && '0' !== (string) $postRegex;
        }
    }

    if ($timeEnabled || $regexEnabled) {
        PaletteManipulator::create()
            ->addField('enableSilentDropSysLog', 'enableRegexSpamProtection', PaletteManipulator::POSITION_AFTER)
            ->applyToPalette('default', 'tl_form');
    }
};
