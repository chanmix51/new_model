<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

use PommProject\Foundation\ResultIterator as FoundationResultIterator;
use PommProject\Foundation\Session\Session;

class ResultIterator implements \Iterator, \Countable, \JsonSerializable, \SeekableIterator {
    public function __construct(private FoundationResultIterator $iterator, private string $entity_class_name) {}

    private function toEntity(array $result): Entity
    {
        $class_name = $this->entity_class_name;

        return $class_name::hydrate($result);
    }

    public function seek(int $offset): void
    {
        $this->iterator->seek($offset);
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function current(): mixed
    {
        return $this->toEntity($this->iterator->current());
    }

    public function key(): mixed
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function count(): int
    {
        return $this->iterator->count();
    }

    public function jsonSerialize(): mixed
    {
        throw new \Exception("not yet implemented");

        return "";
    }

    public function isEmpty(): bool {
        return $this->iterator->isEmpty();
    }
}