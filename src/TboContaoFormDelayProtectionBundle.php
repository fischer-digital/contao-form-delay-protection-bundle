<?php

declare(strict_types=1);

namespace Tbo\FormDelayProtection;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TboContaoFormDelayProtectionBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
