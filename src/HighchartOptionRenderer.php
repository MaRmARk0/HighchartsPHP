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

use JsonException;
use stdClass;

class HighchartOptionRenderer
{
    /**
     * Render the options and returns the javascript that represents them
     *
     * @param mixed $options The options to render
     *
     * @return string The javascript code
     * @throws JsonException in case of json_encode error
     */
    public static function render(mixed $options): string
    {
        $jsExpressions = [];
        //Replace any js expression with random strings, so we can switch
        //them back after json_encode the options
        $options = static::_replaceJsExpr($options, $jsExpressions);

        $result = json_encode($options, JSON_THROW_ON_ERROR);

        //Replace any js expression on the json_encoded string
        foreach ($jsExpressions as $key => $expr) {
            $result = str_replace('"' . $key . '"', $expr, $result);
        }

        return $result;
    }

    /**
     * Replaces any HighchartJsExpr for an id, and save the js expression on the jsExpressions array
     * Based on Zend_Json
     *
     * @param mixed                    $data          The data to analyze
     * @param array<array-key, mixed> &$jsExpressions The array that will hold information about the replaced js expressions
     *
     * @return mixed
     */
    protected static function _replaceJsExpr(mixed $data, array &$jsExpressions): mixed
    {
        if (!is_array($data) && !is_object($data)) {
            return $data;
        }

        if (is_object($data)) {
            if ($data instanceof stdClass) {
                return $data;
            } elseif (!$data instanceof HighchartJsExpr) {
                $data = $data->getValue();
            }
        }

        if ($data instanceof HighchartJsExpr) {
            $magicKey = '____' . count($jsExpressions) . '_' . count($jsExpressions);
            $jsExpressions[$magicKey] = $data->getExpression();

            return $magicKey;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = static::_replaceJsExpr($value, $jsExpressions);
            }
        }

        return $data;
    }
}
