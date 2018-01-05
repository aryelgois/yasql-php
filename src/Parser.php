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
 * Controller class to simplify the package usage
 *
 * @author Aryel Mota Góis
 * @license MIT
 */
class Parser
{
    /**
     * Identifier patterns accepted in a SQL
     *
     * @see https://dev.mysql.com/doc/refman/5.7/en/identifiers.html
     *
     * @var string[]
     */
    const IDENTIFIER_PATTERNS = [
        'unquoted' => '[0-9a-zA-Z$_\x{0080}-\x{FFFF}]',
        'quoted' => '[\x{0001}-\x{007F}\x{0080}-\x{FFFF}]'
    ];

    /**
     * Set of Index keywords
     *
     * The value determines if a table can have one or more indexes
     *
     * @var string
     */
    const INDEX_KEYWORDS = [
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
     * Creates a new Parser object
     *
     * @param string $yasql A string following YAML Ain't SQL specifications
     *
     * @throws \InvalidArgumentException $yasql is not a mapping
     * @throws \RuntimeException         Missing Database name
     * @throws \DomainException          Unsupported source
     * @throws \DomainException          Unknown index
     * @throws \LogicException           Missing composite identifiers
     * @throws \LogicException           Duplicated composite for single column key
     * @throws \LengthException          Missing column definition
     * @throws \RuntimeException         Syntax error in Foreign Key
     * @throws \LogicException           Multiple AUTO_INCREMENT indexes
     * @throws \LengthException          Column is empty
     * @throws \LogicException           Multiple PRIMARY KEY indexes
     */
    public function __construct(string $yasql)
    {
        $data = Yaml::parse($yasql);

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
                $quotes = ['`', '`'];
                $quotes_escaped = ['``', '``'];
                break;

            default:
                throw new \DomainException('Unsupported source');
                break;
        }

        /*
         * Define identifier patterns
         */
        $unquoted = '(' . self::IDENTIFIER_PATTERNS['unquoted'] . '+)';
        $quoted = $quotes[0]
            . '((?:(?![' . implode('', $quotes) . '])'
            . self::IDENTIFIER_PATTERNS['quoted']
            . '|' . $quotes_escaped[0] . '|' . $quotes_escaped[1] . ')+)'
            . $quotes[1];

        /*
         * Define Foreign Key pattern
         */

        $pattern = "/-> ($unquoted *\. *$unquoted|$quoted *\. *$unquoted|$unquoted *\. *$quoted|$quoted *\. *$quoted)( |$)/u";

        /*
         * Expand composite
         */
        $indexes = [];
        $id_keys = self::INDEX_KEYWORDS;
        foreach ($data['composite'] ?? [] as $composite) {
            $result = self::extractKeyword(
                $composite,
                '^((' . implode('|', array_keys($id_keys)) . ')( KEY|))',
                $type
            );
            if ($result !== false) {
                $key = $type[2][0];
            } else {
                $key = explode(' ', $composite)[0];
                throw new \DomainException("Unknown index '$key'");
            }

            if (preg_match_all(
                "/($quoted|$unquoted)/u",
                $result,
                $matches,
                PREG_SET_ORDER
            )) {
                $identifiers = [];
                foreach ($matches as $match) {
                    $match = array_filter($match);
                    $identifiers[] = array_pop($match);
                }
            } else {
                $message = "Missing identifiers in composite '$composite'";
                throw new \LogicException($message);
            }

            $table = array_shift($identifiers);
            if ($id_keys[$key] == 'single' && isset($indexes[$table][$key])) {
                $message = 'Duplicated composite for single column key on table'
                    . " `$table`";
                throw new \LogicException($message);
            }

            if ($id_keys[$key] == 'multiple') {
                $indexes[$table][$key][] = $identifiers;
            } else {
                $indexes[$table][$key] = $identifiers;
            }
        }

        /*
         * Loop through each column
         */
        $tables = $data['tables'] ?? [];
        $definitions = $data['definitions'] ?? [];
        $auto_increment = [];
        $foreigns = [];
        foreach ($tables as $table => $columns) {
            $primary_key = [];
            foreach ($columns as $column => $query) {
                /*
                 * Pre validation
                 */
                $query = trim($query);
                if (strlen($query) == 0) {
                    $message = "Missing column definition in `$table`.`$column`";
                    throw new \LengthException($message);
                }

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
                        $message = 'Syntax error in Foreign Key on column '
                            . "`$table`.`$column`";
                        throw new \RuntimeException($message);
                    }
                    $len = strlen($matches[0]);
                    $query = trim(substr_replace($query, '', $fk, $len));
                    $matches = array_slice(array_filter($matches), 2, 2);
                    $foreigns[$table][$column] = $matches;
                }

                /*
                 * Extract keywords
                 */
                $result = self::extractKeyword($query, 'UNSIGNED');
                if ($result !== false) {
                    $query = $result;
                    $unsigned = true;
                } else {
                    $unsigned = false;
                }

                $result = self::extractKeyword($query, 'ZEROFILL');
                if ($result !== false) {
                    $query = $result;
                    $zerofill = ' ZEROFILL';
                } else {
                    $zerofill = '';
                }

                $result = self::extractKeyword($query, 'AUTO_INCREMENT');
                if ($result !== false) {
                    $query = $result;
                    if (isset($auto_increment[$table])) {
                        $message = "Multiple AUTO_INCREMENT on table `$table`";
                        throw new \LogicException($message);
                    }
                    $auto_increment[$table] = $column;
                }

                $result = self::extractKeyword($query, 'PRIMARY( KEY|)');
                if ($result !== false) {
                    $query = $result;
                    $primary_key[] = $column;
                }

                $result = self::extractKeyword($query, 'UNIQUE( KEY|)');
                if ($result !== false) {
                    $query = $result;
                    $indexes[$table]['UNIQUE'][] = [$column];
                }

                $result = self::extractKeyword(
                    $query,
                    '((DEFAULT|COMMENT|COLUMN_FORMAT|STORAGE|REFERENCES).*)$',
                    $keywords
                );
                if ($result !== false) {
                    $query = $result;
                    $keywords = $keywords[0][0];
                } else {
                    $keywords = '';
                }

                /*
                 * YASQL keywords
                 */
                if (self::strContains($query, self::NUMERIC_TYPES)) {
                    $sign = strpos($query, '+');
                    if ($sign !== false) {
                        $query = substr_replace($query, '', $sign, 1);
                    } elseif ($sign === false || $unsigned) {
                        $query .= ' UNSIGNED';
                    }
                    $query .= $zerofill;
                }

                $result = self::extractKeyword(
                    $query,
                    '(NOT NULL|NULLABLE|NULL)',
                    $key
                );
                if ($result !== false) {
                    $key = ($key[1][0] == 'NULLABLE')
                        ? 'NULL'
                        : $key[1][0];
                    $query = $result . ' ' . $key;
                } else {
                    $query .= ' NOT NULL';
                }

                /*
                 * Restore keywords
                 */
                $query .= $keywords;

                /*
                 * Validation
                 */
                $query = trim($query);
                if (strlen($query) == 0) {
                    $message = "Column `$table`.`$column` is empty";
                    throw new \LengthException($message);
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
                    $message = "Multiple PRIMARY KEY on table `$table`";
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
        $data['auto_increment'] = $auto_increment;
        $data['indexes'] = $indexes;
        $data['foreigns'] = $foreigns;

        $this->data = $data;
    }

    /**
     * Extracts a keyword from a string
     *
     * @param string $haystack String to look for the keyword
     * @param string $needle   PCRE subpattern with the keyword (insensitive)
     * @param string $matches  @see \preg_match() $matches (PREG_OFFSET_CAPTURE)
     *
     * @return false  If the keyword was not found
     * @return string The string without the keyword
     */
    protected static function extractKeyword(
        string $haystack,
        string $needle,
        &$matches = null
    ) {
        $pattern = '/' . (strpos($needle, '^') === 0 ? '' : ' ?') . $needle
            . (strrpos($needle, '$') === strlen($needle)-1 ? '' : ' ?') . '/i';

        if (preg_match($pattern, $haystack, $matches, PREG_OFFSET_CAPTURE)) {
            $m = $matches[0];
            $haystack = substr_replace($haystack, ' ', $m[1], strlen($m[0]));
            return trim($haystack);
        }
        return false;
    }

    /**
     * Returns the parsed data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
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
