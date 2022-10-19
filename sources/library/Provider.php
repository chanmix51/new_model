<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

use PommProject\Foundation\Client\ClientInterface;
use PommProject\Foundation\Session\Session;
use PommProject\Foundation\Where;

interface Provider extends ClientInterface {
    public function findWhere(Where $where): ResultIterator;
    public function getProjectionMap(): ProjectionMap;
    public function getEntityType(): string;
    public function initialize(Session $session);
    public function shutdown();
    public function getSource(string $name): SqlSource;
}
