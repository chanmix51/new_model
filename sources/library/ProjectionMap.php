<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

use InvalidArgumentException;

class ProjectionFieldDefinition {
    public function __construct(public string $source_name, public string $definition, public string $field_name, public string $type) {}

    public function expand(array $sources_alias): string 
    {
        if (! array_key_exists($this->source_name, $sources_alias)) {
            throw new InvalidArgumentException(
                sprintf(
                    "The projection field name '%s' cannot be expanded as its source '%s' is not in the source alias list '{%s}'.",
                    $this->field_name,
                    $this->source_name,
                    join(", ",  array_map(function($v) { return sprintf("'%s'", $v); }, array_keys($sources_alias)))
                )
            );
        }
        $source_alias = $sources_alias[$this->source_name];
        $output = sprintf("%s as %s", strtr($this->definition, ["**" => $source_alias]), $this->field_name);

        return $output;
    }

    public static function fromStructure(string $source_name, Structure $structure): array
    {
        $projection_fields = [];

        foreach ($structure->getDefinition() as $field_name => $type) {
            $projection_fields[$field_name] = new ProjectionFieldDefinition($source_name, sprintf('**."%s"', $field_name), $field_name, $type);
        }

        return $projection_fields;
    }
}


interface ProjectionMap {
    /// expand the projection in a SQL statement
    public function expand(array $sources_alias): string;

    /// get the output structure
    public function getStructure(): Structure;
}
