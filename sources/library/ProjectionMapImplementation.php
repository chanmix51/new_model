<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

trait ProjectionMapImplementation {
    protected array $projection = [];

    public static function fromStructure(Structure $structure): array {
        $definition = [];

        foreach ($structure->getDefinition() as $name => $type) {
            $definition[$name] = new FieldDefinition(sprintf('"%s"', $name), $type);
        }

        return $definition;
    }

    public function getType(string $field_name): ?string {
        return isset($this->projection[$field_name]) ?
            $this->projection[$field_name]->type
            : null;
    }

    public function getDefinition(string $name): ?string {
        return isset($this->projection[$name]) ?
            $this->projection[$name]->definition
            : null;
    }

    public function fieldExists(string $name): bool {
        return isset($this->projection[$name]);
    }

    public function expand(?string $alias): string {
        $output = [];
        $alias = $alias === null ? "" : $alias . ".";

        foreach ($this->projection as $name => $definition) {
            $output[] = sprintf("%s as %s", $alias . $definition->definition, $name);
        }

        return join(', ', $output);
    }
}
