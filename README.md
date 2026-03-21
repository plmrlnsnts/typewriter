# ⌨️ Typewriter

Typewriter is a GraphQL code generation tool to easily generate PHP classes and enums from your GraphQL schema and GraphQL operations.

```bash
vendor/bin/typewriter
```

## Features

- [x] Converts GraphQL schemas and operations into strongly-typed PHP code
- [x] Generates classes, enums, and input types for your GraphQL client
- [x] Supports custom type casting and namespace organization
- [x] Automates code generation for GraphQL queries

## Installation

```bash
composer require --dev plmrlnsnts/typewriter

vendor/bin/typewriter init
```

## Configuration

After installation, a `typewriter.json` configuration file is created in your project root.

```json
{
  "schemas": [
    {
      "source": "schema.json",
      "enums": {
        "directory": "app/Graphql/Enums",
        "namespace": "App\\Graphql\\Enums"
      },
      "inputs": {
        "directory": "app/Graphql/Inputs",
        "namespace": "App\\Graphql\\Inputs"
      },
      "entrypoints": [
        {
          "input": "app",
          "output": "app/Graphql/Data",
          "namespace": "App\\Graphql\\Data"
        }
      ]
    }
  ]
}
```

### Available Options

- **schemas**: List of schema configurations. Each object describes how to generate code for a GraphQL schema.
  - **source**: Path to your GraphQL introspection JSON file (e.g., `schema.json`).
  - **enums**: Where to generate PHP enums.
    - **directory**: Output directory for generated enum classes.
    - **namespace**: PHP namespace for generated enums.
  - **inputs**: Where to generate PHP input classes.
    - **directory**: Output directory for generated input classes.
    - **namespace**: PHP namespace for generated inputs.
  - **entrypoints**: List of entrypoints for code generation. Each entrypoint defines a set of GraphQL operations to generate code for.
    - **input**: Directory or file containing GraphQL operation documents (e.g., queries, mutations).
    - **output**: Output directory for generated data classes.
    - **namespace**: PHP namespace for generated data classes.

### Custom Casts

You can customize how GraphQL scalar types are mapped to PHP types or classes by using the `casts` option in your schema configuration. This is useful when you want to use custom value objects, type wrappers, or specific PHP classes for certain GraphQL scalars (e.g., mapping `DateTime` to a custom `DateTime` class instead of a string).

Add a `casts` object inside your schema configuration, where each key is a GraphQL scalar type and each value is the fully qualified PHP class name to use for that type.

```json
{
  "casts": {
    "DateTime": "App\\Graphql\\Casts\\DateTime",
    "ID": "App\\Graphql\\Casts\\Identity"
  }
}
```

### Extending Types

You can customize the base class that all generated types will extend by specifying the `type` option in your schema configuration. By default, generated classes extend the built-in `Plmrlnsnts\Typewriter\Type`, but you may want to use your own. For example, using [spatie/laravel-data](https://github.com/spatie/laravel-data):

```json
{
  "type": "Spatie\\LaravelData\\Data"
}
```

### Downloading schema.json

To generate a `schema.json` from your GraphQL endpoint, you can use [get-graphql-schema](https://github.com/prisma-labs/get-graphql-schema) or similar tools.

```bash
npm install -g get-graphql-schema

get-graphql-schema https://your.graphql.endpoint > schema.json
```

## Author

Paul Santos
paulmarlonsantos@gmail.com
