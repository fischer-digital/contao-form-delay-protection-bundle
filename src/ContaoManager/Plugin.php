<?php

declare(strict_types=1);

namespace Tbo\FormDelayProtection\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Tbo\FormDelayProtection\TboContaoFormDelayProtectionBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(TboContaoFormDelayProtectionBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    ContaoManagerBundle::class,
                ]),
        ];
    }
}
