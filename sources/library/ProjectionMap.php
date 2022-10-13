<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

class FieldDefinition {

    public function __construct(
        public string $definition,
        public string $type
    ) {}
}

interface ProjectionMap {
    public static function fromStructure(Structure $structure): Self;

    public function expand(?string $alias): string;
}
/// example:
/// SELECT count(pika) as chu FROM …
/// `count(pika) as chu` is a field definition with
/// `chu` => field alias
/// `count(pika)` as field definition
/// `int` as field type
trait ProjectionMapImplementation {
    // field name => field definition
    private array $projection = [];

    public function getType(string $name): ?string {
        return isset($this->projection[$name]) ?
            $this->projection[$name]->type
            : null;
    }

    public function getDefinition(string $name): ?string {
        return isset($this->projection[$name]) ?
            $this->projection[$name]->definition
            : null;
    }

    public function aliasExists(string $name): bool {
        return isset($this->projection[$name]);
    }

    public function expand(?string $alias): string {
        $output = [];
        $alias = $alias === null ? "" : $alias . ".";

        foreach ($this->projection as $name => $definition) {
            $output[] = sprintf("%s as %s", $alias . $definition, $name);
        }

        return join(', ', $output);
    }
}