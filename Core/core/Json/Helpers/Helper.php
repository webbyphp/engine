<?php

/*
| -------------------------------------------------------------------------
| Json Helper Functions
| -------------------------------------------------------------------------
|
*/

namespace Base\Json\Helpers;

class Helper
{
    /**
     * Get JSON Chunk
     *
     * @param string|mixed $file
     * @param integer $startDepth
     * @return mixed
     */
    public static function getJsonChunk($file, int $startDepth = -1)
    {
        $bufferSize = 8192;
        $start = false;
        $quotes = [false, false];
        $depth = 0;
        $totalBytesRead = 0;
        $end = false;
        $currentPosition = 0;

        if (!$file) {
            return;
        }

        $currentPosition = ftell($file);
        // Get total size of file
        fseek($file, 0, SEEK_END);
        $size = ftell($file);
        rewind($file);

        while (
            !feof($file)
        ) {
            $buffer = fread($file, $bufferSize);
            $i = 0;

            if (false !== $buffer) {
                // So I do not have to do strlen to get read size which is linear
                $readCount = ($size > $bufferSize) ? $bufferSize : $size;
                $size -= $readCount;

                if (
                    false === $start
                ) {
                    // Find first occurence of the curly bracket
                    $start = strpos($buffer, '{');
                    if (false === $start) {
                        $totalBytesRead += $readCount;
                        continue;
                    } else {
                        $i = $start + 1;
                        $start += $totalBytesRead;
                        $totalBytesRead += $readCount;
                    }
                } else {
                    $totalBytesRead += $readCount;
                }

                for (; isset($buffer[$i]); $i++) {
                    if (
                        "'" == $buffer[$i] && !$quotes[1]
                    ) {
                        // If quote is escaped, ignore
                        if (!empty($buffer[$i - 1]) && '\\' == $buffer[$i - 1]) {
                            continue;
                        }

                        $quotes[0] = !$quotes[0];
                        continue;
                    }

                    if (
                        '"' == $buffer[$i] && !$quotes[0]
                    ) {
                        // If quote is escaped, ignore
                        if (!empty($buffer[$i - 1]) && '\\' == $buffer[$i - 1]) {
                            continue;
                        }

                        $quotes[1] = !$quotes[1];
                        continue;
                    }

                    $isQuoted = in_array(true, $quotes, true);

                    if (
                        '{' == $buffer[$i] && !$isQuoted
                    ) {
                        if ($depth == $startDepth) {
                            $start = $totalBytesRead - $readCount + $i;
                        }
                        $depth++;
                    }

                    if (
                        '}' == $buffer[$i] && !$isQuoted
                    ) {
                        $depth--;
                        if ($depth == $startDepth) {
                            $end = $totalBytesRead - $readCount + $i + 1;
                            break 2;
                        }
                    }
                }
            }
        }

        $chunk = '';

        if (false !== $start && false !== $end) {
            fseek(
                $file,
                $start,
                SEEK_SET
            );
            $chunk = fread($file, $end - $start);
        }

        fseek(
            $file,
            $currentPosition,
            SEEK_SET
        );

        return $chunk;
    }

    /**
     * Converts objects to array
     *
     * @param mixed $object object(s)
     *
     * @return array|mixed
     */
    public static function objectToArray($object)
    {
        // Not an array or object? Return back what was given
        if (!is_array($object) && !is_object($object))
            return $object;

        $array = (array) $object;

        foreach ($array as $key => $value) {
            $array[$key] = Helper::objectToArray($value);
        }

        return $array;
    }
}
