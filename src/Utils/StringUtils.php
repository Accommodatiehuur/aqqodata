<?php

namespace Aqqo\OData\Utils;

/**
 * @class StringUtils
 * The class below contains all static functions for strings.
 */
class StringUtils
{

    /**
     * Functions splits the odata expression into groups, with each group in between parentheses.
     *
     * @param string $expr
     * @return array<int, string>
     */
    public static function splitODataExpression(string $expr): array
    {
        // Early return if there are no parentheses
        if (!str_contains($expr, '(') && !str_contains($expr, ')')) {
            return [$expr];
        }

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
            } elseif ($char === ')') {
                $depth--;
            }

            // Check for ' and ' or ' or ' at depth 0
            if ($depth === 0 && (substr($expr, $i, 5) === ' and ' || substr($expr, $i, 4) === ' or ')) {
                $current = trim($current);
                if ($current !== '') {
                    $result[] = $current;
                }

                // Add operator and reset current
                $result[] = substr($expr, $i, 5) === ' and ' ? 'and' : 'or';
                $current = '';
                $i += substr($expr, $i, 5) === ' and ' ? 5 : 4;
                continue;
            }

            // Accumulate current character
            $current .= $char;
            $i++;
        }

        // Add any remaining segment
        if (($current = trim($current)) !== '') {
            $result[] = $current;
        }

        return $result;
    }

    /**
     * @param string $details
     * @return array
     */
    public static function getSortedDetails(string $details): array
    {
        $details = preg_split('/;(?![^(]*\))/', $details);
        $sorted = [];

        foreach ($details as $detail) {
            if (str_starts_with($detail, '$select')) {
                $sorted[0] = $detail;
            } else if (str_starts_with($detail, '$expand')) {
                $sorted[1] = $detail;
            } else if (str_starts_with($detail, '$filter')) {
                $sorted[2] = $detail;
            }
        }
        ksort($sorted);
        return array_values($sorted);
    }
}
