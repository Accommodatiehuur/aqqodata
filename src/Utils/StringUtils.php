<?php

namespace Aqqo\OData\Utils;

class StringUtils
{
    /**
     * @param string $expr
     * @return array
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

    public static function getQueryParts(string $expr): array
    {
        // Use preg_split to explode the string based on ' or ' and ' and '
        $conditions = preg_split('/\s+(and|or)\s+/i', $expr);

// Use preg_match_all to find the operators
        preg_match_all('/\s+(and|or)\s+/i', $expr, $operators);

// Combine conditions and operators
        $result = [];
        $operatorIndex = 0;

        foreach ($conditions as $index => $condition) {
            $result[] = trim($condition); // Add the condition
            if ($operatorIndex < count($operators[0])) {
                $result[] = trim($operators[0][$operatorIndex]); // Add the operator
                $operatorIndex++;
            }
        }

// Remove the last operator added (if exists)
        if (end($result) === 'and' || end($result) === 'or') {
            array_pop($result);
        }

        return $result;
    }
}
