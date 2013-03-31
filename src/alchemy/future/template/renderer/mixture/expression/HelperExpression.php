<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\future\template\renderer\mixture\expression;

use alchemy\future\template\renderer\mixture\IExpression;
use alchemy\future\template\renderer\mixture\Node;
use alchemy\future\template\renderer\mixture\Compiler;

class HelperExpression implements IExpression
{

    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    public static function isBlock()
    {
        return false;
    }

    public static function getOpenTag()
    {
        return '*';
    }

    public static function getCloseTag()
    {
    }

    public function handle(Compiler $compiler)
    {
        $parameters = $this->node->getParameters();
        $helper = array_shift($parameters);
        $params = array();
        if ($parameters) {



            foreach ($parameters as $p) {
                $p = '\'' . addcslashes($p, '\'') . '\'';
                preg_match_all('#[^\\\](\$[a-z0-9\.-_]+)#i', $p, $m);
                if ($m[0]) {

                    for ($i = 0; $i < count($m[0]); $i++) {
                        $p = str_replace($m[1][$i], ('\' . ' . VarExpression::getVariableReference($m[1][$i]) . ' . \'') , $p);
                    }
                    $params[] = $p;
                } else {
                    $params[] = $p;
                }
            }

            $compiler->appendText('<?php alchemy\future\template\renderer\Mixture::callHelper(\'' . $helper . '\', array(' . implode(',', $params) . ', $this));?>');
        } else {
            $compiler->appendText('<?php alchemy\future\template\renderer\Mixture::callHelper(\'' . $helper . '\', array($this));?>');
        }
    }

    /**
     * @var \alchemy\future\template\renderer\mixture\Node
     */
    protected $node;

}
