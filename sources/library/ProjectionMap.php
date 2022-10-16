<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

class FieldDefinition {

    public function __construct(
        public string $definition,
        public string $type
    ) {}
}

interface ProjectionMap {
    /// create a ProjectionMap from a database structure
    /// create a ProjectionMap definition from a database structure
    public static function fromStructure(Structure $structure): array;

    /// return the type of the given projection field 
    public function getType(string $field_name): ?string;

    /// return a field definition 
    public function getDefinition(string $name): ?string;

    /// does the given field exist?
    public function fieldExists(string $name): bool;

    /// expand the projection in a SQL statement
    public function expand(?string $alias): string;
}
