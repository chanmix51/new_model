<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

class Structure {
    
    protected array $definition = []; 

    public function __contruct(array $definition) {
        $this->definition = $definition;
    }

    public function setField(string $name, string $type): Self {
        $this->definition[$name] = $type;

        return $this;
    }

    public function getDefinition(): array {
        return $this->definition;
    }
}