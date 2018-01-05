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

        $in = str_repeat(' ', max(0, $indent ?? 2));

        $db = $data['database'];

        /*
         * Generate Header
         */
        $sql = "-- Generated with yasql-php\n"
            . "-- https://github.com/aryelgois/yasql-php\n"
            . "--\n"
            . '-- Timestamp: ' . date('c') . "\n"
            . '-- PHP version: ' . phpversion() . "\n";

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
            $sql .= "--\n";
            foreach ($header as $k => $v) {
                $sql .= "-- $k: $v\n";
            }
        }

        $sql .= "\n"
            . "CREATE DATABASE IF NOT EXISTS `{$db['name']}`\n"
            . $in . 'CHARACTER SET ' . ($db['charset'] ?? 'utf8') . "\n"
            . $in . 'COLLATE ' . ($db['collate'] ?? 'utf8_general_ci') . ";\n"
            . "\nUSE `{$db['name']}`;\n\n";

        /*
         * Generate SQL
         */
        $tables = $indexes = $autos = $foreigns = null;
        foreach ($data['tables'] as $table => $columns) {
            /*
             * Add Table
             */
            foreach ($columns as $column => $query) {
                $columns[$column] = "$in`$column` $query";
            }
            $tables = ($tables ?? "--\n-- Tables\n--\n\n")
                . "CREATE TABLE `$table` (\n"
                . implode("\n", Utils::arrayAppendLast($columns, "\n", ','))
                . ') CHARACTER SET ' . ($db['charset'] ?? 'utf8') . ";\n\n";

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

                        case 'INDEX':
                        case 'UNIQUE':
                            foreach ($index as $column) {
                                $id[] = $in . "ADD $key KEY (`"
                                    . implode('`, `', $column) . '`)';
                            }
                            break;
                    }
                }
                $indexes = ($indexes ?? "--\n-- Indexes\n--\n\n")
                    . "ALTER TABLE `$table`\n"
                    . implode("\n", Utils::arrayAppendLast($id, ";\n\n", ','));
            }

            /*
             * Add Foreigns
             */
            $foreign_list = $data['foreigns'][$table] ?? [];
            if (!empty($foreign_list)) {
                $fk = [];
                foreach ($foreign_list as $column => $foreign) {
                    $fk[] = $in . "ADD FOREIGN KEY (`$column`) REFERENCES"
                        . " `{$foreign[0]}` (`{$foreign[1]}`)";
                }
                $foreigns = ($foreigns ?? "--\n-- Foreigns\n--\n\n")
                    . "ALTER TABLE `$table`\n"
                    . implode("\n", Utils::arrayAppendLast($fk, ";\n\n", ','));
            }
        }

        /*
         * Add AUTO_INCREMENT
         */
        foreach ($data['auto_increment'] ?? [] as $table => $column) {
            $autos = ($autos ?? "--\n-- AUTO_INCREMENT\n--\n\n")
                . "ALTER TABLE `$table`\n"
                . $in . "MODIFY `$column` "
                . $data['tables'][$table][$column] . " AUTO_INCREMENT;\n\n";
        }

        $this->sql = $sql . $tables . $indexes . $autos . $foreigns;
    }

    /**
     * Returns the generated SQL
     *
     * @return string
     */
    public function output()
    {
        return trim($this->sql) . "\n";
    }
}
