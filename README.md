# Intro

This is a PHP implementation for **YAML Ain't SQL**, whose specification can be
found in [aryelgois/yasql].


# Install

Enter the terminal, navigate to your project root and run:

`composer require aryelgois/yasql-php`


# Setup

For easier access to the builder, add the following to your `composer.json`:

```json
{
    "scripts": {
        "yasql-builder" : "aryelgois\\YaSql\\Controller::build"
    }
}
```

And create a config file `databases.yml` somewhere.  
_(see specifications in **Builder**)_


# Usage

Create databases following the [YASQL][aryelgois/yasql] schema and add them in
your `databases.yml`. Then run the following command inside your project root:

`composer run-script yasql-builder -- path/to/databases.yml`

> **NOTE**
>
> All paths (inside `databases.yml` and in the previous command) are relative to
> your project root.

It will create `.sql` files in the output directory, so you can import them into
your sql server.


# API

This package provides some classes to parse YASQL and generate SQL. They are
under the namespace `aryelgois\YaSql`.


## Controller

This class wrapps the others, to make them easier to use.

- _static_ **build(** [Event] $event **)**

  Use this method with `composer run-script` to build your databases into a
  specific directory. It receives an argument with the path to a config file.  
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

- **__construct(** [string] $config \[, [string] $root \] **)**

  Generates a list of databases stored in the filesystem and outputs in a
  specific directory. The paths are relative to `$root`, which is the current
  working directory by default.


#### config file

A YAML with a mapping of the following keys:

- `databases`: sequence of files with YASQL database schemas. It can be a
  string or a mapping of the YASQL path and a post sql
- `output`: path to a directory were the generated files will be stored
- `indentation`: _(optional)_ used during the sql generation

Example:

```yaml
databases:
- tests/example.yml
- path: data/mydatabase.yml
  post: data/mydatabase_populate.sql

indentation: 4

output: build/databases
```

The post file is useful for pre populated rows or to apply sql commands not
covered by YASQL specification. Its content is appended to the generated sql.


[aryelgois/yasql]: https://github.com/aryelgois/yasql
[Event]: https://getcomposer.org/apidoc/master/Composer/Script/Event.html
[int]: https://secure.php.net/manual/en/language.types.integer.php
[string]: https://secure.php.net/manual/en/language.types.string.php
[Parser]: src/Parser.php
