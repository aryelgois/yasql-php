<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

use Symfony\Component\Yaml\Yaml;

/**
 * Create SQL database schemas with YAML
 *
 * @author Aryel Mota Góis
 * @license MIT
 */
class YaSql
{
    /**
     * Types which receive the UNSIGNED attribute
     *
     * @const string[]
     */
    const NUMERIC_TYPES = [
        'tinyint',
        'smallint',
        'mediumint',
        'int',
        'integer',
        'bigint',
        'real',
        'double',
        'float',
        'decimal',
        'numeric',
    ];

    /**
     * The parsed YAML with a database description
     *
     * @var array
     */
    protected $data;

    /**
     * How many spaces per indentation level
     *
     * @var int
     */
    public $indentation = 2;

    /**
     * Parses a string following YAML Ain't SQL specifications
     *
     * @param string $yaml Contains a database description
     *
     * @throws \InvalidArgumentException $yaml is not a mapping
     * @throws \RuntimeException         Missing Database name
     * @throws \DomainException          Unsupported source
     * @throws \RuntimeException         Syntax error in Foreign Key
     * @throws \LengthException          Column is empty
     */
    public function __construct($yaml)
    {
        $data = Yaml::parse($yaml);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('YASQL must be a mapping');
        }
        if (!isset($data['database']['name'])) {
            throw new \RuntimeException('Database needs a name');
        }

        /*
         * Define quotation marks
         */
        $source = $data['database']['source'] ?? 'MySQL';
        switch ($source) {
            case 'MySQL':
                $quotes = '``';
                break;

            default:
                throw new \DomainException('Unsupported source');
                break;
        }
        $qO = $quotes[0]; // Open
        $qC = $quotes[1]; // Close

        /*
         * Define Foreign Key pattern
         */
        $pattern = '/-> ('
            . '(\w+)\.(\w+)|'
            . $qO . '(\w+)' . $qC . '\.(\w+)|'
            . '(\w+)\.' . $qO . '(\w+)' . $qC . '|'
            . $qO . '(\w+)' . $qC . '\.' . $qO . '(\w+)' . $qC
            . ')/';

        /*
         * Loop through each column
         */
        $tables = $data['tables'] ?? [];
        $definitions = $data['definitions'] ?? [];
        $foreigns = [];
        foreach ($tables as $table => $columns) {
            foreach ($columns as $column => $query) {
                /*
                 * Expand definitions
                 */
                if (!empty($definitions)) {
                    while ($tokens = explode(' ', $query)) {
                        if (array_key_exists($tokens[0], $definitions)) {
                            $tokens[0] = $definitions[$tokens[0]];
                            $query = implode(' ', $tokens);
                        } else {
                            break;
                        }
                    }
                }

                /*
                 * Extract Foreign Key
                 */
                $fk = strpos($query, '->');
                if ($fk !== false) {
                    preg_match($pattern, substr($query, $fk), $matches);
                    if (empty($matches)) {
                        $mesage = 'Syntax error in Foreign Key on column "'
                            . $table . '.' . $column . '"';
                        throw new \RuntimeException($mesage);
                    }
                    $len = strlen($matches[0]);
                    $query = substr_replace($query, '', $fk, $len);
                    $matches = array_slice($matches, -2, 2);
                    $foreigns[$table][$column] = $matches;
                }

                /*
                 * Validation
                 */
                $query = trim($query);
                if (strlen($query) == 0) {
                    throw new \LengthException('Column is empty');
                }

                /*
                 * Defaults
                 */
                $sign = strpos($query, '+');
                if ($sign === false) {
                    if (self::strContains($query, self::NUMERIC_TYPES)) {
                        if (stripos($query, 'UNSIGNED') === false) {
                            $query .= ' UNSIGNED';
                        }
                    }
                } else {
                    $query = substr_replace($query, '', $sign, 1);
                }

                if (stripos($query, 'NOT NULL') === false) {
                    if (stripos($query, 'NULL') === false) {
                        $query .= ' NOT NULL';
                    } else {
                        $query = str_replace('NULLABLE', 'NULL', $query);
                    }
                }

                $tables[$table][$column] = $query;
            }
        }

        $data['tables'] = $tables;
        unset($data['definitions']);
        $data['foreigns'] = $foreigns;

        $this->data = $data;
    }

    /**
     * Outputs SQL commands to create the database
     *
     * @return string
     */
    public function output()
    {
        $in = $this->indentation;
        if (!is_integer($in) || $in < 0) {
            $in = 2;
        }
        $in = str_repeat(' ', $in);

        $db = $this->data['database'];

        $sql = [
            '-- PHP YASQL output',
            '-- https://github.com/aryelgois/yasql-php',
            '--',
            '-- Timestamp: ' . date('c'),
            '-- PHP version: ' . phpversion(),
            '',
            'CREATE DATABASE IF NOT EXISTS `' . $db['name'] . '`',
            $in . 'CHARACTER SET ' . ($db['charset'] ?? 'utf8'),
            $in . 'COLLATE ' . ($db['collate'] ?? 'utf8_general_ci') . ';',
            '',
            'USE `' . $db['name'] . '`;',
            '',
        ];

        $tables = [
            '--',
            '-- Tables',
            '--',
            '',
        ];

        $indexes = [
            '--',
            '-- Indexes',
            '--',
            '',
        ];

        $foreigns = [
            '--',
            '-- Foreigns',
            '--',
            '',
        ];

        foreach ($this->data['tables'] as $table => $columns) {
            $count = count($columns);
            foreach ($columns as $column => $query) {
                $columns[$column] = $in . '`' . $column . '` ' . $query;
                if (--$count > 0) {
                    $columns[$column] .= ',';
                }
            }
            $tables = array_merge(
                $tables,
                ['CREATE TABLE `' . $table . '` ('],
                $columns,
                [');', '']
            );
        }

        $sql = array_merge(
            $sql,
            $tables,
            $indexes,
            $foreigns
        );

        return implode("\n", $sql) . "\n";
    }

    /**
     * Tells if a string contains any items in an array (case insensitive)
     *
     * @author zombat
     * @link https://stackoverflow.com/a/2124557
     *
     * @param string $str A string to be tested
     * @param array  $arr List of substrings that could be in $str
     *
     * @return bool
     */
    protected static function strContains($str, array $arr)
    {
        foreach ($arr as $a) {
            if (stripos($str, $a) !== false) {
                return true;
            }
        }
        return false;
    }
}
