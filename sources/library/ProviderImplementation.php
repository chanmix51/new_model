<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

use PommProject\Foundation\Session\Session;

trait ProviderImplementation {
    private Session $session;
    private ProjectionMap $projection;

    public function getSession(): Session {
        return $this->session;
    }

    public function getProjectionMap(): ProjectionMap {
        return $this->projection;
    }

    public function query(string $sql, array $parameters = []): ResultIterator {
        $session = $this->getSession();
        $class = $this->getEntityType();
        $iterator = $session->getQueryManager()
            ->query($sql, $parameters);

        return new ResultIterator($iterator, $class);
    }

    public function getClientType() {
        return 'provider';
    }

    public function getClientIdentifier() {
        return get_class($this);
    }

    public function shutdown() {}
}
