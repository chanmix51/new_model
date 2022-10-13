# New Model

Takes on Pomm new model layer

## Definitions

 * projection_map: mapping between source and target fields
 * sql_source:  SQL definition that can be set as a FROM (can be a table name, a SQL query etc.)
 * structure: an association between a field names => type
 * provider: a provider is fetching data according to a projection

API

  SqlSource:
    - getDefinition() -> string
    - getStructure() -> Structure

  Structure:
    - getDefinition(string $alias) -> array[string => string]

  Provider:
    - findWhere(Where $where) -> ResultIterator
    - getProjectionMap() -> ProjectionMap

  ProjectionMap:
    - fromStructure(Structure $structure) -> ProjectionMap