<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

interface Entity {
    public static function hydrate(array $value): Self;
}