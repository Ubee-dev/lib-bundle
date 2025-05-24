<?php

namespace Khalil1608\LibBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Types\Type as BaseType;

/**
 * The base class for so-called Doctrine mapping types.
 *
 * A Type object is obtained by calling the static {@link getType()} method.
 */
abstract class Type extends BaseType
{
    public const string MONEY = 'money';
    public const string Email = 'email';
    public const string Name = 'name';
    public const string Url = 'url';
    public const string HtmlName = 'htmlName';
}
