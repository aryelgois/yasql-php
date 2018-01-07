<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

use Symfony\Component\Yaml\Yaml;

/**
 * Generate SQL commands to populate a database schema
 *
 * You could simply store the .sql full of INSERT INTO statements, but it is not
 * maintainable, and the file would be bigger than a compact YAML with the same
 * usable data.
 *
 * Use this class to generate these statements on the fly, when generating the
 * database schema. Add the following in the `post` key of your config file:
 *
 * ```yaml
 * databases:
 * - path: path/to/database.yml
 *   post:
 *   - call: Fully\Qualified\Class # Replace the class by one that works for
 *     with: path/to/source.yml    # your database. You can create one by
 *                                 # implementing this class. `with` can be
 *                                 # a sequence of paths
 * ```
 *
 * @author Aryel Mota Góis
 * @license MIT
 * @link https://www.github.com/aryelgois/yasql-php
 */
abstract class Populator
{
    /**
     * Data to generate the SQL
     *
     * @var array
     */
    protected $data;

    /**
     * File loaded
     *
     * @var string
     */
    protected $filename;

    /**
     * Root directory to load files
     *
     * @var string
     */
    protected $root;

    /**
     * Creates a new Populator object
     *
     * @param string $root Root directory to load files
     */
    public function __construct(string $root)
    {
        $this->root = $root;
    }

    /**
     * Loads a YAML file to be processed
     *
     * @param string $file Path to YAML source file
     */
    public function load(string $file)
    {
        $this->data = Yaml::parse(file_get_contents($this->root . '/' . $file));
        $this->filename = basename($file);
    }

    /**
     * Generates SQL with INSERT INTO statements from $data
     *
     * @return string
     */
    abstract public function run();
}
