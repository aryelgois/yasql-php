<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

/**
 * Generate SQL commands to create a database schema
 *
 * @author Aryel Mota Góis
 * @license MIT
 */
class Generator
{
    /**
     * The generated SQL
     *
     * @var string
     */
    protected $sql;

    /**
     * Creates a new Generator object
     *
     * @param Parser $parser A valid Parser object
     * @param int    $indent How many spaces per indentation level
     */
    public function __construct(Parser $parser, int $indent = null)
    {
        $data = $parser->getData();

        $in = str_repeat(' ', $indent ?? 2);

        $db = $data['database'];

        /*
         * Generate Header
         */
        $sql = [
            '-- Generated with yasql-php',
            '-- https://github.com/aryelgois/yasql-php',
            '--',
            '-- Timestamp: ' . date('c'),
            '-- PHP version: ' . phpversion(),
        ];

        $header = array_filter([
            'Project'     => $db['project'] ?? '',
            'Description' => $db['description'] ?? '',
            'Version'     => $db['version'] ?? '',
            'License'     => $db['license'] ?? '',
            'Authors'     => implode(
                "\n--          ",
                (array) ($db['authors'] ?? '')
            ),
        ]);
        if (!empty($header)) {
            array_walk($header, function (&$v, $k) {
                $v = '-- ' . $k . ': ' . $v;
            });
            array_unshift($header, '--');
        }

        $sql = array_merge($sql, array_values($header), [
            '',
            'CREATE DATABASE IF NOT EXISTS `' . $db['name'] . '`',
            $in . 'CHARACTER SET ' . ($db['charset'] ?? 'utf8'),
            $in . 'COLLATE ' . ($db['collate'] ?? 'utf8_general_ci') . ';',
            '',
            'USE `' . $db['name'] . '`;',
            '',
        ]);

        /*
         * Generate SQL
         */
        foreach ($data['tables'] as $table => $columns) {
            /*
             * Add Table
             */
            foreach ($columns as $column => $query) {
                $columns[$column] = $in . '`' . $column . '` ' . $query;
            }
            $tables = array_merge(
                $tables ?? [
                    '--',
                    '-- Tables',
                    '--',
                    '',
                ],
                ['CREATE TABLE `' . $table . '` ('],
                array_values(self::arrayAppendLast($columns, '', ',')),
                [
                    ') CHARACTER SET ' . ($db['charset'] ?? 'utf8') . ';',
                    ''
                ]
            );

            /*
             * Add Indexes
             */
            $index_list = $data['indexes'][$table] ?? [];
            if (!empty($index_list)) {
                $id = [];
                foreach ($index_list as $key => $index) {
                    switch ($key) {
                        case 'PRIMARY':
                            $id[] = $in . 'ADD PRIMARY KEY (`'
                                . implode('`, `', $index) . '`)';
                            break;

                        case 'UNIQUE':
                            foreach ($index as $column) {
                                $id[] = $in . 'ADD UNIQUE KEY (`'
                                    . implode('`, `', $column) . '`)';
                            }
                            break;
                    }
                }
                $indexes = array_merge(
                    $indexes ?? [
                        '--',
                        '-- Indexes',
                        '--',
                        '',
                    ],
                    ['ALTER TABLE `' . $table . '`'],
                    self::arrayAppendLast($id, ';', ','),
                    ['']
                );
            }

            /*
             * Add Foreigns
             */
            $foreign_list = $data['foreigns'][$table] ?? [];
            if (!empty($foreign_list)) {
                $f = [];
                foreach ($foreign_list as $column => $foreign) {
                    $f[] = $in . 'ADD FOREIGN KEY (`' . $column . '`) '
                        . 'REFERENCES `' . $foreign[0]
                        . '` (`' . $foreign[1] . '`)';
                }
                $foreigns = array_merge(
                    $foreigns ?? [
                        '--',
                        '-- Foreigns',
                        '--',
                        '',
                    ],
                    ['ALTER TABLE `' . $table . '`'],
                    self::arrayAppendLast($f, ';', ','),
                    ['']
                );
            }
        }

        /*
         * Add AUTO_INCREMENT
         */
        foreach ($data['auto_increment'] ?? [] as $table => $column) {
            $auto_increments = array_merge(
                $auto_increments ?? [
                    '--',
                    '-- AUTO_INCREMENT',
                    '--',
                    '',
                ],
                [
                    'ALTER TABLE `' . $table . '`',
                    $in . 'MODIFY `' . $column . '` ' .
                    $data['tables'][$table][$column] . ' AUTO_INCREMENT;',
                    ''
                ]
            );
        }

        $sql = array_merge(
            $sql,
            $tables ?? [],
            $indexes ?? [],
            $auto_increments ?? [],
            $foreigns ?? []
        );

        $this->sql = implode("\n", $sql);
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

    /**
     * Returns teh generated SQL
     *
     * @return string
     */
    public function output()
    {
        return $this->sql;
    }
}
