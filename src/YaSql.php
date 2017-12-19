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
     * Set of Index keywords
     *
     * The value determines if a table can have one or more indexes
     *
     * @var string
     */
    const INDEX_KEYWORDS = [
        //'FOREIGN' => 'multiple',
        'PRIMARY' => 'single',
        'UNIQUE' => 'multiple',
    ];

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
     * @throws \DomainException          Unknown index
     * @throws \LogicException           Duplicated composite for single key
     * @throws \RuntimeException         Syntax error in Foreign Key
     * @throws \LengthException          Column is empty
     * @throws \LogicException           Multiple PRIMARY KEY index
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
         * Expand composite
         */
        $indexes = [];
        $keywords = self::INDEX_KEYWORDS;
        foreach ($data['composite'] ?? [] as $composite) {
            $tokens = explode(' ', $composite);
            if ($tokens[1] == 'KEY') {
                unset($tokens[1]);
                $tokens = array_values($tokens);
            }

            $key = strtoupper($tokens[0]);
            if (!array_key_exists($key, $keywords)) {
                throw new \DomainException('Unknown index "' . $key . '"');
            }

            $table = $tokens[1];
            if ($keywords[$key] == 'single' && isset($indexes[$table][$key])) {
                $message = 'Duplicated composite for single key on table "'
                    . $table . '"';
                throw new \LogicException($message);
            }

            $columns = array_slice($tokens, 2);

            if ($keywords[$key] == 'multiple') {
                $indexes[$table][$key][] = $columns;
            } else {
                $indexes[$table][$key] = $columns;
            }
        }

        /*
         * Loop through each column
         */
        $tables = $data['tables'] ?? [];
        $definitions = $data['definitions'] ?? [];
        $foreigns = [];
        $flag = PREG_OFFSET_CAPTURE;
        foreach ($tables as $table => $columns) {
            $primary_key = [];
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
                 * Extract indexes
                 */
                $p = '/ ?PRIMARY( KEY|) ?/i';
                if (preg_match($p, $query, $matches, $flag)) {
                    $m = $matches[0];
                    $query = substr_replace($query, ' ', $m[1], strlen($m[0]));
                    $primary_key[] = $column;
                }

                if (preg_match('/ ?UNIQUE ?/i', $query, $matches, $flag)) {
                    $m = $matches[0];
                    $query = substr_replace($query, ' ', $m[1], strlen($m[0]));
                    $indexes[$table]['UNIQUE'][] = [$column];
                }

                /*
                 * Validation
                 */
                $query = trim($query);
                if (strlen($query) == 0) {
                    $message = 'Column "' . $table . '.' . $column
                        . '" is empty';
                    throw new \LengthException($message);
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

                /*
                 * Store
                 */
                $tables[$table][$column] = $query;
            }

            /*
             * Add PRIMARY KEY
             *
             * If multiple columns have this attribute, it will be a composite.
             * PHP has ordered associative arrays, so it will be in the same
             * order as in the YAML. Other languages might produce a composite
             * in another order
             */
            if (!empty($primary_key)) {
                if (isset($indexes[$table]['PRIMARY'])) {
                    $message = 'Multiple PRIMARY KEY on table "' . $table . '"';
                    throw new \LogicException($message);
                } else {
                    $indexes[$table]['PRIMARY'] = $primary_key;
                }
            }
        }

        /*
         * Update data and store
         */
        $data['tables'] = $tables;
        unset($data['composite'], $data['definitions']);
        $data['indexes'] = $indexes;
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
            /*
             * Add Table
             */
            foreach ($columns as $column => $query) {
                $columns[$column] = $in . '`' . $column . '` ' . $query;
            }
            $tables = array_merge(
                $tables,
                ['CREATE TABLE `' . $table . '` ('],
                self::arrayAppendLast($columns, '', ','),
                [');', '']
            );

            /*
             * Add Indexes
             */
            $index = $this->data['indexes'][$table] ?? [];
            if (!empty($index)) {
                $id = [];
                foreach ($index as $key => $value) {
                    switch ($key) {
                        case 'PRIMARY':
                            $id[] = $in . 'ADD PRIMARY KEY (`'
                                . implode('`, `', $value) . '`)';
                            break;

                        case 'UNIQUE':
                            foreach ($value as $v) {
                                $id[] = $in . 'ADD UNIQUE KEY (`'
                                    . implode('`, `', $v) . '`)';
                            }
                            break;
                    }
                }
                $indexes = array_merge(
                    $indexes,
                    ['ALTER TABLE `' . $table . '`'],
                    self::arrayAppendLast($id, ';', ','),
                    ['']
                );
            }

            /*
             * Add Foreigns
             */
            $foreign_list = $this->data['foreigns'][$table] ?? [];
            if (!empty($foreign_list)) {
                $f = [];
                foreach ($foreign_list as $column => $foreign) {
                    $f[] = $in . 'ADD FOREIGN KEY (`' . $column . '`) '
                        . 'REFERENCES `' . $foreign[0]
                        . '` (`' . $foreign[1] . '`)';
                }
                $foreigns = array_merge(
                    $foreigns,
                    ['ALTER TABLE `' . $table . '`'],
                    self::arrayAppendLast($f, ';', ','),
                    ['']
                );
            }
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

    /**
     * Appends a string to the last item in an array
     *
     * Optionally, appends a string to the other items
     *
     * @param string[] $array  Array to receive data
     * @param string   $last   Appended to the last item
     * @param string   $others Appended to the other items
     */
    protected static function arrayAppendLast($array, $last, $others = '')
    {
        $count = count($array);
        foreach ($array as $key => $value) {
            $array[$key] = $value . (--$count > 0 ? $others : $last);
        }
        return $array;
    }
}
