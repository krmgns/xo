<?php
declare(strict_types=1);

namespace arrays;

use arrays\ArraysException;

/**
 * @package arrays
 * @object  arrays\Arrays
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final /* static */ class Arrays extends StaticClass
{
    /**
     * Set (with dot notation support for sub-array paths).
     * @param  array      &$array
     * @param  int|string $key
     * @param  any        $valueDefault
     * @return any
     */
    public static function set(array &$array, $key, $value): array
    {
        if (array_key_exists($key, $array)) { // direct access
            $array[$key] = $value;
        } else {
            $keys = explode('.', (string) $key);
            if (count($keys) <= 1) { // direct access
                $array[$key] = $value;
            } else { // path access (with dot notation)
                $current = &$array;
                foreach($keys as $key) {
                    $current = &$current[$key];
                }
                $current = $value;
                unset($current);
            }
        }

        return $array;
    }

    /**
     * Get (with dot notation support for sub-array paths).
     * @param  array      $array
     * @param  int|string $key
     * @param  any        $valueDefault
     * @return any
     */
    public static function get(array $array, $key, $valueDefault = null)
    {
        if (array_key_exists($key, $array)) { // direct access
            $value = $array[$key];
        } else {
            $keys = explode('.', (string) $key);
            if (count($keys) <= 1) { // direct access
                $value = $array[$key] ?? null;
            } else { // path access (with dot notation)
                $value = &$array;
                foreach ($keys as $key) {
                    if (!is_array($value) || !array_key_exists($key, $value)) {
                        $value = null;
                        break;
                    }
                    $value = &$value[$key];
                }
            }
        }

        return $value ?? $valueDefault;
    }

    /**
     * Get all (shortcuts like: list(..) = Arrays::getAll(..)).
     * @param  array  $array
     * @param  array  $keys (aka paths)
     * @param  any    $valueDefault
     * @return array
     */
    public static function getAll(array $array, array $keys, $valueDefault = null): array
    {
        $values = [];
        foreach ($keys as $key) {
            if (is_array($key)) { // default value given as array
                @ [$key, $valueDefault] = $key;
            }
            $values[] = self::get($array, $key, $valueDefault);
        }

        return $values;
    }

    /**
     * Pull.
     * @param  array      &$array
     * @param  int|string $key
     * @param  any        $valueDefault
     * @return any
     */
    public static function pull(array &$array, $key, $valueDefault = null)
    {
        if (array_key_exists($key, $array)) {
            $value = $array[$key];
            unset($array[$key]); // remove pulled item
        }

        return $value ?? $valueDefault;
    }

    /**
     * Pull all.
     * @param  array  &$array
     * @param  array  $keys
     * @param  any    $valueDefault
     * @return array
     */
    public static function pullAll(array &$array, array $keys, $valueDefault = null): array
    {
        $values = [];
        foreach ($keys as $key) {
            if (is_array($key)) { // default value given as array
                @ [$key, $valueDefault] = $key;
            }
            $values[] = self::pull($array, $key, $valueDefault);
        }

        return $values;
    }

    /**
     * Test (like JavaScript Array.some()).
     * @param  array    $array
     * @param  callable $func
     * @return bool
     */
    public static function test(array $array, callable $func): bool
    {
        foreach ($array as $key => $value) {
            try {
                if ($func($value, $key)) return true; // try user function
            } catch (\ArgumentCountError $e) {
                if ($func($value)) return true; // try an internal single-argument function like is_*
            }
        }
        return false;
    }

    /**
     * Test all (like JavaScript Array.every()).
     * @param  array    $array
     * @param  callable $func
     * @return bool
     */
    public static function testAll(array $array, callable $func): bool
    {
        foreach ($array as $key => $value) {
            try {
                if (!$func($value, $key)) return false; // try user function
            } catch (\ArgumentCountError $e) {
                if (!$func($value)) return false; // try an internal single-argument function like is_*
            }
        }
        return true;
    }

    /**
     * Sort.
     * @param  array        &$array
     * @param  callable|null $func
     * @param  callable|null $ufunc
     * @param  int           $flags
     * @return array
     * @throws arrays\ArraysException
     */
    public static function sort(array &$array, callable $func = null, callable $ufunc = null, int $flags = 0): array
    {
        if ($func == null) {
            sort($array, $flags);
        } elseif ($func instanceof \Closure) {
            usort($array, $func);
        } elseif (is_string($func)) {
            if ($func[0] == 'u' && $ufunc == null) {
                throw new ArraysException("Second argument must be callable when usort,uasort,".
                    "uksort given");
            }
            $arguments = [&$array, $flags];
            if ($ufunc != null) {
                if (in_array($func, ['sort', 'asort', 'ksort'])) {
                    $func = 'u'. $func; // update to user function
                }
                $arguments[1] = $ufunc; // replace flags with ufunc
            }
            call_user_func_array($func, $arguments);
        }

        return $array;
    }

    /**
     * Include.
     * @param  array $array
     * @param  array $keys
     * @return array
     */
    public static function include(array $array, array $keys): array
    {
        return array_filter($array, function($_, $key) use($keys) {
            return in_array($key, $keys);
        }, 1);
    }

    /**
     * Exclude.
     * @param  array $array
     * @param  array $keys
     * @return array
     */
    public static function exclude(array $array, array $keys): array
    {
        return array_filter($array, function($_, $key) use($keys) {
            return !in_array($key, $keys);
        }, 1);
    }

    /**
     * First.
     * @param  array $array
     * @param  any   $valueDefault
     * @return any|null
     */
    public static function first(array $array, $valueDefault = null)
    {
        return array_values($array)[0] ?? $valueDefault;
    }

    /**
     * Last.
     * @param  array $array
     * @param  any   $valueDefault
     * @return any|null
     */
    public static function last(array $array, $valueDefault = null)
    {
        return array_values($array)[count($array) - 1] ?? $valueDefault;
    }

    /**
     * Get int.
     * @param  array      $array
     * @param  int|string $key
     * @param  any|null   $valueDefault
     * @return int
     */
    public static function getInt(array $array, $key, $valueDefault = null): int
    {
        return (int) self::get($array, $key, $valueDefault);
    }

    /**
     * Get float.
     * @param  array      $array
     * @param  int|string $key
     * @param  any|null   $valueDefault
     * @return float
     */
    public static function getFloat(array $array, $key, $valueDefault = null): float
    {
        return (float) self::get($array, $key, $valueDefault);
    }

    /**
     * Get string.
     * @param  array      $array
     * @param  int|string $key
     * @param  any|null   $valueDefault
     * @return string
     */
    public static function getString(array $array, $key, $valueDefault = null): string
    {
        return (string) self::get($array, $key, $valueDefault);
    }

    /**
     * Get bool.
     * @param  array      $array
     * @param  int|string $key
     * @param  any|null   $valueDefault
     * @return bool
     */
    public static function getBool(array $array, $key, $valueDefault = null): bool
    {
        return (bool) self::get($array, $key, $valueDefault);
    }
}
