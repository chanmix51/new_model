<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

use PommProject\Foundation\ResultIterator;
use PommProject\Foundation\Where;
use PommProject\Foundation\Session;

interface Provider {
    public function findWhere(Where $where): ResultIterator;
    public function getProjectionMap(): ProjectionMap;
    public function query(string $sql, array $parameters = []): ResultIterator;
}

trait ProviderImplementation {
    private Session $session;
    private ProjectionMap $projection;

    public function getSession(): Session {
        return $this->sessions;
    }

    public function getProjectionMap(): ProjectionMap {
        return $this->projection;
    }

    public function query(string $sql, array $parameters = []): ResultIterator {
        $session = $this->getSession();
        $iterator = $session->getQueryManager()
            ->query($sql, $parameters);

        return $iterator;
    }
}