<?php

namespace Aqqo\OData\Utils;

class StringUtils
{
    /**
     * @param string $expr
     * @return array<int, string>
     */
    public static function splitODataExpression(string $expr): array
    {
        if (!str_contains($expr, '(') && !str_contains($expr, ')')) {
            return [$expr];
        };

        $result = [];
        $current = '';
        $depth = 0;
        $length = strlen($expr);
        $i = 0;
        while ($i < $length) {
            $char = $expr[$i];
            // Handle parentheses
            if ($char === '(') {
                $depth++;
                $current .= $char;
                $i++;
                continue;
            } elseif ($char === ')') {
                $depth--;
                $current .= $char;
                $i++;
                continue;
            }
            // Check for ' and ' or ' or ' only at depth 0
            if ($depth === 0) {
                // Check for ' and ' or ' or '
                if (substr($expr, $i, 5) === ' and ') {
                    // Add current segment
                    $current = trim($current);
                    if ($current !== '') {
                        $result[] = $current;
                    }
                    // Add the operator
                    $result[] = 'and';
                    // Reset current
                    $current = '';
                    // Move past ' and '
                    $i += 5;
                    continue;
                } elseif (substr($expr, $i, 4) === ' or ') {
                    // Add current segment
                    $current = trim($current);
                    if ($current !== '') {
                        $result[] = $current;
                    }
                    // Add the operator
                    $result[] = 'or';
                    // Reset current
                    $current = '';
                    // Move past ' or '
                    $i += 4;
                    continue;
                }
            }
            // Accumulate current character
            $current .= $char;
            $i++;
        }
        // Add any remaining segment
        $current = trim($current);
        if ($current !== '') {
            $result[] = $current;
        }
        return $result;
    }
}
