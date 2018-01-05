# Intro

This is a PHP implementation for **YAML Ain't SQL**, whose specification can be
found in [aryelgois/yasql].


# Install

Enter the terminal, navigate to your project root and run:

`composer require aryelgois/yasql-php`


# Setup

This package provides CLI tools to work with YASQL files. To use them, add the
following in your `composer.json`:

```json
{
    "scripts": {
        "yasql-build": "aryelgois\\YaSql\\Composer::build",
        "yasql-generate": "aryelgois\\YaSql\\Composer::generate"
    }
}
```

You also need a config file for the builder command. The default is to place it
in `config/databases.yml`, but you can choose another place or have more than
one configuration.  
_(see specifications in **Builder**)_


# Usage

#### yasql-build

Create databases following the [YASQL][aryelgois/yasql] schema and add them in
your builder configuration (`databases.yml`). Then run the following command
inside your project root:

`composer yasql-build -- path/to/output [path/to/config_file.yml]`

> **NOTE**
>
> - All paths (inside the config file and in the previous command) are relative
>   to your project root
>
> - If you use the default config file location `config/databases.yml`, it is
>   optional
>
> - It might be a good idea to add the output directory to your .gitignore

It will create `.sql` files in the output directory, so you can import them into
your sql server.


#### yasql-generate

If you only want to generate the SQL from one YASQL schema, run the following
command:

`composer yasql-generate -- path/to/yasql.yml [indentation]`

It will output to stdout, so you can add something like ` > output_database.sql`
to write the result in a file. The indentation defaults to 2 spaces.


# API

This package provides some classes to parse YASQL and generate SQL. They are
under the namespace `aryelgois\YaSql`.


## Composer

Provides Composer scripts to use this package from the command line.  
_(see how to configure the commands in **Setup**)_

- _static_ **build(** [Event] $event **)**

  It receives an argument with the path to output directory and a optional
  config file (defaults to `config/databases.yml`).

- _static_ **generate(** [Event] $event **)**

  The first argument is the path to a YASQL file, the second is a optional
  indentation to be used (default is 2 spaces).


## Controller

This class wrapps others, to make them easier to use.

- _static_ **build(** [string] $root , [string] $output , [string] $config [, [string] $vendors] **)**

  Use this method to build your databases into a specific directory.  
  (see **Builder**)

- _static_ **generate(** [string] $yasql \[, [int] $indent \] **)**

  Use this to generate the SQL from a YASQL and get the result in a string.  
  (see **Generator**)

- _static_ **parse(** [string] $yasql **)**

  Use it to dump the parsed data from a YASQL. Basically, good for debugging.  
  (see **Parser**)


## Parser

- **__construct(** [string] $yasql **)**

  It parses a YASQL and extracts some data from each column, making them ready
  for the Generator.

  See the `$yasql` specification [here][aryelgois/yasql].

- **getData()**

  Retrieves the parsed data in a multidimensional array.


## Generator

- **__construct(** [Parser] $parser \[, [int] $indent \] **)**

  Produces SQL to create the database. It asks for a Parser to ensure the data
  is valid.

- **output()**

  Retrieves the generated SQL in a multi line string.


## Builder

- **__construct(** [string] $output \[, [string] $vendors \] **)**

  Creates a new Builder object. Databases will go into `$output`, and vendors
  are searched in `$vendors`. Both paths can be absolut or relative, and the
  latter is by default `vendor` in the current working directory.

- **build(** [string] $config \[, [string] $root \] **)**

  Generates a list of databases from the filesystem into the object's output
  directory. The paths are relative to `$root`, which is the current working
  directory by default.

- **getLog()**

  Retrieves log information from the build process.


#### config file

A YAML with the following keys: (all are optional)

- `databases`: sequence of files with YASQL database schemas. It can be a
  string or a mapping of the YASQL path and a post sql (or a sequence of post
  files)
- `indentation`: used during the sql generation
- `vendors`: a map of vendors installed by Composer to config files inside them.
  It can be a string (for a single config) or a sequence of paths. They are
  relative to the vendor package root

Example:

```yaml
databases:
  - tests/example.yml
  - path: data/mydatabase.yml
    post: data/mydatabase_populate.sql

indentation: 4

vendors:
  someone/package: config/databases.yml # could be ~ (yaml null) for the default
```

The post file is useful for pre populated rows or to apply sql commands not
covered by YASQL specification. Its content is appended to the generated sql.


## Populator

A helper class for **Builder**. Use it to generate `INSERT INTO` statements to
populate your databases.

This class is _abstract_, so you have to write a class that extends it. The
reason is that the YAML with the data might be in a arbitrary layout, depending
on your database schema.

To use it, you need a special post in the builder config:

Example from [aryelgois/databases]:

```yml
databases:
  - path: data/address.yml
    post:
      - data/address/populate_countries.sql
      - call: aryelgois\Databases\AddressPopulator
        with:
          - data/address/source/Brazil.yml
```

The post must map to a sequence, and the item is a map of:

- `call`: a fully qualified class that extends **Populator**, autoloadable by
  Composer
- `with`: path to a YAML with the data to be processed. It can be a sequence


## Utils

There is also a class with utility methods. They are used internally and can be
used by whoever require this package.

- **arrayAppendLast(** [array] $array , [string] $last \[, [string] $others \] **)**

  Appends a string to the last item. Optionally, appends a string to the others.
  It is useful to generate sql statements with a list of comma separated items,
  and a semicolon at the last item.


[aryelgois/yasql]: https://github.com/aryelgois/yasql
[aryelgois/databases]: https://github.com/aryelgois/databases
[Event]: https://getcomposer.org/apidoc/master/Composer/Script/Event.html
[array]: https://secure.php.net/manual/en/language.types.array.php
[int]: https://secure.php.net/manual/en/language.types.integer.php
[string]: https://secure.php.net/manual/en/language.types.string.php
[Parser]: src/Parser.php
