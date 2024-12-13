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
    public static function splitODataExpression(string $expression): array
    {
        $parts = [];
        $current = '';
        $parenthesesLevel = 0;

        $length = strlen($expression);
        for ($i = 0; $i < $length; $i++) {
            $char = $expression[$i];

            if ($char === '(') {
                $parenthesesLevel++;
            } elseif ($char === ')') {
                if ($parenthesesLevel > 0) {
                    $parenthesesLevel--;
                } else {
                    // Handle unexpected closing parenthesis
                    throw new \InvalidArgumentException("Unbalanced parentheses in expression.");
                }
            }

            if ($char === ',' && $parenthesesLevel === 0) {
                // Top-level comma; split here
                $parts[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        // Add the last part
        if (trim($current) !== '') {
            $parts[] = $current;
        }

        // Optional: Validate balanced parentheses
        if ($parenthesesLevel !== 0) {
            throw new \InvalidArgumentException("Unbalanced parentheses in expression.");
        }

        return $parts;
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
