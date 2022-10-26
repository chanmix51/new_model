<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

trait ProjectionMapImplementation {
    // ARRAY[string (field_name) => ProjectionFieldDefinition]
    protected array $definition = [];

    public function expand(array $sources_alias): string {
        $output = [];

        foreach ($this->projection as $name => $definition) {
            $output[] = $definition->expand($sources_alias);
        }


        return join(', ', $output);
    }

    public function getStructure(): Structure {
        $definition = [];

        foreach ($this->projection as $field_name => $field_definition) {
            $definition[$field_name] = $field_definition->type;
        }

        return new Structure($definition);
    }
}
