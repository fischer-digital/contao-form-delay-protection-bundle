<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/**
 * Extends the tl_form DCA with time-based form delay protection fields.
 */

// --- Fields ---

$GLOBALS['TL_DCA']['tl_form']['fields']['enableTimeBasedSpamProtection'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['enableTimeBasedSpamProtection'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true],
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['minLoadTime'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['minLoadTime'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => [3, 5, 10, 15],
    'reference' => &$GLOBALS['TL_LANG']['tl_form']['minLoadTimeReference'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(4) NOT NULL default '5'",
];

// --- Selector + Subpalette ---

$GLOBALS['TL_DCA']['tl_form']['palettes']['__selector__'][] = 'enableTimeBasedSpamProtection';
$GLOBALS['TL_DCA']['tl_form']['subpalettes']['enableTimeBasedSpamProtection'] = 'minLoadTime';

// --- Append fields to end of config_legend ---

PaletteManipulator::create()
    ->addField(['enableTimeBasedSpamProtection'], 'storeSession', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_form');
