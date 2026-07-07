<?php

namespace App\Support\Purify;

use HTMLPurifier_HTMLDefinition;
use Stevebauman\Purify\Definitions\Definition;
use Stevebauman\Purify\Definitions\Html5Definition;

final class TipContentDefinition implements Definition
{
    public static function apply(HTMLPurifier_HTMLDefinition $definition): void
    {
        Html5Definition::apply($definition);

        $definition->addAttribute('img', 'data-media-id', 'Text');
    }
}
