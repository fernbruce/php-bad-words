<?php

namespace Fernbruce\PhpBadWords\DfaFilter;

use Fernbruce\PhpBadWords\DfaFilter\Exceptions\PdsSystemException;

function mb_strlen($str, $encoding = null)
{
    $length = \mb_strlen($str, $encoding);
    if ($length === false) {
        throw new PdsSystemException(' encoding 无效');
    }

    return $length;
}