<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\future\template\html\form;
class TextArea extends Input
{
    public function __toString()
    {
        return sprintf(self::TEMPLATE, $this->attributesToString('value'), htmlentities($this->value));
    }

    const TEMPLATE = '<textarea %s>%s</textarea>';
}