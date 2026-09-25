<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

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

// --- Selector + Subpalette ---

$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'enableTimeBasedSpamProtection';
$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'enableRegexSpamProtection';
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['enableTimeBasedSpamProtection'] = 'minLoadTime';
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['enableRegexSpamProtection'] = 'regexSpamExcludeFields';

// --- Append fields to end of config_legend ---

PaletteManipulator::create()
    ->addField(['enableTimeBasedSpamProtection', 'enableRegexSpamProtection', 'enableSilentDrop'], 'storeSession', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_form');
