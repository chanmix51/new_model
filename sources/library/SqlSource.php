<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

interface SqlSource {
    public function getStructure(): Structure;

    public function getDefinition(): string;
}