<?php

namespace Aqqo\OData\Utils;

use Illuminate\Support\Str;

/**
 * Class StringUtils
 *
 * Provides utility functions for string manipulation, specifically tailored for OData expressions.
 */
class StringUtils
{
    /**
     * Splits an OData expression into its constituent parts, respecting nested parentheses.
     *
     * @param string $expr The OData expression to split.
     * @return array<string> The split components of the expression.
     */
    public static function splitODataExpression(string $expr): array
    {
        // Early return if there are no parentheses or logical operators
        if (!Str::contains($expr, ['(', ')', ' and ', ' or '])) {
            return [trim($expr)];
        }

        $result = [];
        $current = '';
        $depth = 0;
        $length = strlen($expr);
        $i = 0;

        while ($i < $length) {
            $char = $expr[$i];

            // Handle opening parenthesis
            if ($char === '(') {
                $depth++;
            }
            // Handle closing parenthesis
            elseif ($char === ')') {
                $depth--;
            }

            // Check for logical operators at depth 0
            if ($depth === 0) {
                // Check for ' and ' (length 5) and ' or ' (length 4)
                if (substr($expr, $i, 5) === ' and ') {
                    $result[] = trim($current);
                    $result[] = 'and';
                    $current = '';
                    $i += 5;
                    continue;
                } elseif (substr($expr, $i, 4) === ' or ') {
                    $result[] = trim($current);
                    $result[] = 'or';
                    $current = '';
                    $i += 4;
                    continue;
                }
            }

            // Accumulate the current character
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
     * Splits a details string into individual components and sorts them in a predefined order.
     *
     * The order is:
     * 1. $select
     * 2. $expand
     * 3. $filter
     *
     * @param string $details The details string to split and sort.
     * @return array<string> The sorted detail components.
     */
    public static function getSortedDetails(string $details): array
    {
        // Split by semicolons not within parentheses
        $parts = preg_split('/;(?![^()]*\))/', $details);

        if ($parts === false) {
            return [];
        }

        // Define the desired order of details
        $order = [
            '$select' => 1,
            '$expand' => 2,
            '$filter' => 3,
        ];

        // Initialize an associative array to store sorted details
        $sorted = [];

        foreach ($parts as $part) {
            $part = trim($part);
            foreach ($order as $key => $priority) {
                if (Str::startsWith($part, $key)) {
                    $sorted[$priority] = $part;
                    break;
                }
            }
        }

        // Sort the details based on the defined priority
        ksort($sorted);

        // Return the sorted details as a numerically indexed array
        return array_values($sorted);
    }
}
