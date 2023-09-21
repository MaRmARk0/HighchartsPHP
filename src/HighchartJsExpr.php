<?php
/**
 *
 * Copyright 2012-2012 Portugalmail Comunicações S.A (http://www.portugalmail.net/)
 *
 * See the enclosed file LICENCE for license information (GPLv3). If you
 * did not receive this file, see http://www.gnu.org/licenses/gpl-3.0.html.
 *
 * @author Gonçalo Queirós <mail@goncaloqueiros.net>
 */

namespace Ghunti\HighchartsPHP;

class HighchartJsExpr
{
    /**
     * @var string|false The javascript expression
     */
    private string|false $_expression;

    /**
     * The HighchartJsExpr constructor
     *
     * @param string $expression The javascript expression
     */
    public function __construct(string $expression)
    {
        $this->_expression = iconv(
            mb_detect_encoding($expression),
            'UTF-8',
            $expression
        );
    }

    /**
     * Returns the javascript expression
     *
     * @return string|false The javascript expression
     */
    public function getExpression(): string|false
    {
        return $this->_expression;
    }
}
