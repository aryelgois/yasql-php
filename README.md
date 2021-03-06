# YASQL-PHP

Index:

- [Intro]
- [Install]
- [Setup]
- [Usage]
  - [yasql-build]
  - [yasql-generate]
- [API]
  - [Composer]
  - [Controller]
  - [Parser]
  - [Generator]
  - [Builder]
    - [config file]
  - [Populator]
  - [Utils]
- [Changelog]


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

You also need a [config file] for the builder command. The default is to place
it in `config/databases.yml`, but you can choose another place or have more than
one configuration.  
_(see [Builder] specifications)_


# Usage

### yasql-build

First, create databases following the [YASQL][aryelgois/yasql] schema and list
them in your [config file]. Then run the following command in your project root:

```sh
composer yasql-build -- [ config=path/to/config_file.yml | -c ]
                        [ output=path/to/output/ ]
                        [ vendor=vendor/package ]
```

- `config`: Lists YASQL databases to be built and vendors to include
  - The `-c` flag indicates that you do not want any config file. It is useful
    when using `vendor` arguments. It is the same as `config=''`
- `output`: Directory where files are generated
- `vendor`: Additional vendor to include (using default config file location)

Notes:

- Paths in the command line are relative to the project root
- Paths in the config file are relative to the file itself
- Absolut paths are absolut (they start with `/`)
- Vendors are located in Composer's `vendor dir`
- You can omit `config` for using the default `config/databases.yml`
- You can omit `output` for using the default `build/`
- You can add multiple `vendor` arguments
- It might be a good idea to add the output directory to your .gitignore

This command creates `.sql` files in the output directory, so you can import
them into your sql server.


### yasql-generate

If you only want to generate the SQL from one YASQL schema, run the following
command:

`composer yasql-generate -- path/to/yasql.yml [ indentation ]`

The first argument is the path to a YASQL file, the second is a optional
indentation to be used (default is 2 spaces).

It will output to stdout, so you can add something like ` > output_database.sql`
to write the result in a file.


# API

This package provides some classes to parse YASQL and generate SQL. They are
under the namespace `aryelgois\YaSql`.


## [Composer][Composer-class]

Provides Composer scripts to use this package from the command line.  
_(see how to configure the commands in [Setup])_

- _static_ **build(** [Event] $event **)**

  It accepts arguments described in [yasql-build].

- _static_ **generate(** [Event] $event **)**

  It accepts arguments described in [yasql-generate].


## [Controller][Controller-class]

This class wrapps others, to make them easier to use.

- _static_ **build(** [string] $output , [string] $config , [string] $vendor [, [array] $vendors ] **)**

  Use this method to build your databases into a specific directory.  
  _(see [Builder])_

- _static_ **generate(** [string] $yasql [, [int] $indent ] **)**

  Use this to generate the SQL from a YASQL and get the result in a string.  
  _(see [Generator])_

- _static_ **parse(** [string] $yasql **)**

  Use it to dump the parsed data from a YASQL. Basically, good for debugging.  
  _(see [Parser])_


## [Parser][Parser-class]

- **__construct(** [string] $yasql **)**

  It parses a YASQL and extracts some data from each column, making them ready
  for the Generator.

  See the `$yasql` specification [here][aryelgois/yasql].

- **getData()**

  Retrieves the parsed data in a multidimensional array.


## [Generator][Generator-class]

- **__construct(** [Parser][Parser-class] $parser [, [int] $indent ] **)**

  Produces SQL that generates a database. It asks for a Parser object to ensure
  the data is valid.

- **output()**

  Retrieves the generated SQL in a multi line string.


## [Builder][Builder-class]

- **__construct(** [ [string] $output [, [string] $vendor ] ] **)**

  Creates a new Builder object. Databases will go into `$output`, and vendors
  are searched in `$vendors`. Both paths can be absolut or relative, and default
  to `build/` and `vendor/` in the current working directory, respectively.

- **build(** [string] $config [, [array] $vendors ] **)**

  Generates a list of databases listed in `$config` file into the object's
  output directory.

- **getLog()**

  Retrieves log information from the build process.


### config file

A [YAML] with the following keys: (all are optional)

- `databases`: sequence of files with YASQL database schemas. It can be a
  string or a mapping of the YASQL `path` and a `post` sql (or a sequence of
  post files)

  Also, a `name` can be defined to overwrite the database's name. It is useful
  when you want to combine multiple database schemas in a single database. Just
  be careful with conflicting tables. Also note that external foreigns require
  special care in the file order that you run in the sql server

- `indentation`: used during the sql generation
- `vendors`: a map of vendors installed by Composer to config files inside them.
  It can be a string (for a single config) or a sequence of paths. They are
  relative to the vendor package root. Using `~` (yaml null) denotes the
  [default config file path][Setup]

Example:

```yaml
databases:
  - ../tests/example.yml
  - path: ../data/mydatabase.yml
    post: ../data/mydatabase_populate.sql
    name: AwesomeExample

indentation: 4

vendors:
  someone/package: config/databases.yml
```

The post file is useful for pre populated rows or to apply sql commands not
covered by YASQL specification. Its content is appended to the generated sql.


## [Populator][Populator-class]

A helper class for [Builder]. Use it to generate `INSERT INTO` statements to
populate your databases.

This class is _abstract_, so you have to write a class that extends it. The
reason is that the YAML with the data might be in a arbitrary layout, depending
on your database schema.

To use it, you need a special post in the [builder config][config file]:

Example from [aryelgois/databases]:

```yml
databases:
  - path: ../data/address.yml
    post:
      - ../data/address/populate_countries.sql
      - call: aryelgois\Databases\AddressPopulator
        with:
          - ../data/address/source/Brazil.yml
```

The post must map to a sequence, and the desired item is a map of:

- `call`: a fully qualified class that extends [Populator], autoloadable by
  Composer
- `with`: path to a YAML with the data to be processed. It can be a sequence


## [Utils][Utils-class]

There is also a class with utility methods. They are used internally and can be
used by whoever requires this package.

- **arrayAppendLast(** [array] $array , [string] $last [, [string] $others ] **)**

  Appends a string to the last item. Optionally, appends a string to the others.
  It is useful to generate sql statements with a list of comma separated items,
  and a semicolon at the last item.


# [Changelog]


[Setup]: #setup
[Intro]: #intro
[Install]: #install
[Setup]: #setup
[Usage]: #usage
[yasql-build]: #yasql-build
[yasql-generate]: #yasql-generate
[API]: #api
[Composer]: #composer
[Controller]: #controller
[Parser]: #parser
[Generator]: #generator
[Builder]: #builder
[config file]: #config-file
[Populator]: #populator
[Utils]: #utils

[Changelog]: CHANGELOG.md
[Composer-class]: src/Composer.php
[Controller-class]: src/Controller.php
[Parser-class]: src/Parser.php
[Generator-class]: src/Generator.php
[Builder-class]: src/Builder.php
[Populator-class]: src/Populator.php
[Utils-class]: src/Utils.php

[aryelgois/yasql]: https://github.com/aryelgois/yasql
[aryelgois/databases]: https://github.com/aryelgois/databases

[Event]: https://getcomposer.org/apidoc/master/Composer/Script/Event.html

[array]: https://secure.php.net/manual/en/language.types.array.php
[int]: https://secure.php.net/manual/en/language.types.integer.php
[string]: https://secure.php.net/manual/en/language.types.string.php

[YAML]: http://yaml.org/
