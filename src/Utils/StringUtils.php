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
        // Initialize variables for splitting
        $parts = [];
        $current = '';
        $parentheses = 0;
        $length = strlen($details);

        // Iterate over each character to split by semicolons not within parentheses
        for ($i = 0; $i < $length; $i++) {
            $char = $details[$i];

            if ($char === '(') {
                $parentheses++;
            } elseif ($char === ')') {
                if ($parentheses > 0) {
                    $parentheses--;
                }
            }

            if ($char === ';' && $parentheses === 0) {
                $parts[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        // Add the last part if not empty
        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        // Define the desired order of details using an indexed array for flexibility
        $order = [
            '$select',
            '$expand',
            '$filter',
        ];

        // Assign priorities based on the order array
        $priorityMap = [];
        foreach ($order as $index => $key) {
            $priorityMap[$key] = $index + 1; // Start priorities at 1
        }
        $defaultPriority = count($order) + 1;

        // Group parts by their priority
        $grouped = [];

        foreach ($parts as $part) {
            $found = false;
            foreach ($priorityMap as $key => $priority) {
                if (strpos($part, $key) === 0) { // More efficient than Str::startsWith
                    $grouped[$priority][] = $part;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Assign default priority to unknown keys
                $grouped[$defaultPriority][] = $part;
            }
        }

        // Sort the grouped parts by priority
        ksort($grouped);

        // Flatten the grouped parts into a single array
        $sorted = [];
        foreach ($grouped as $priority => $items) {
            foreach ($items as $item) {
                $sorted[] = $item;
            }
        }

        return $sorted;
    }

}
