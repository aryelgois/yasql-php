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
     * The parsed YAML with a database description
     *
     * @var array
     */
    protected $data;

    /**
     * Parses a string following YAML Ain't SQL specifications
     *
     * @param string $yaml Contains a database description
     *
     * @throws \InvalidArgumentException $yaml is not a mapping
     * @throws \RuntimeException         Missing Database name
     * @throws \DomainException          Unsupported source
     * @throws \RuntimeException         Syntax error in Foreign Key
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
        foreach ($tables as $table_name => $columns) {
            foreach ($columns as $column_name => $column) {
                /*
                 * Expand definitions
                 */
                if (!empty($definitions)) {
                    while ($tokens = explode(' ', $column)) {
                        if (array_key_exists($tokens[0], $definitions)) {
                            $tokens[0] = $definitions[$tokens[0]];
                            $column = implode(' ', $tokens);
                        } else {
                            break;
                        }
                    }
                }

                /*
                 * Extract Foreign Key
                 */
                $fk = strpos($column, '->');
                if ($fk !== false) {
                    preg_match($pattern, substr($column, $fk), $matches);
                    if (empty($matches)) {
                        $mesage = 'Syntax error in Foreign Key on column "'
                            . $table_name . '.' . $column_name . '"';
                        throw new \RuntimeException($mesage);
                    }
                    $len = strlen($matches[0]);
                    $column = substr_replace($column, '', $fk, $len);
                    $matches = array_slice($matches, -2, 2);
                    $foreigns[$table_name][$column_name] = $matches;
                }

                $tables[$table_name][$column_name] = $column;
            }
        }

        $data['tables'] = $tables;
        unset($data['definitions']);
        $data['foreigns'] = $foreigns;

        $this->data = $data;
    }
}