<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUnit\Extensions\Selenium2TestCase\ElementCommand;

use PHPUnit\Extensions\Selenium2TestCase\Command;
use PHPUnit\Extensions\Selenium2TestCase\URL;

/**
 * Retrieves the element's coordinates
 */
class Rect extends Command
{
    public function __construct(string $parameter, URL $attributeResourceBaseUrl)
    {
        parent::__construct([], $attributeResourceBaseUrl->descend($parameter));
    }

    public function httpMethod(): string
    {
        return 'GET';
    }
}
