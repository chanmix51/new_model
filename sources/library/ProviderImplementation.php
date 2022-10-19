<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

use PommProject\Foundation\Session\Session;

trait ProviderImplementation {
    private Session $session;
    private ProjectionMap $projection;
    private array $sources = [];

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
        return Self::class;
    }

    public function shutdown() {}

    public function getSource(string $name): SqlSource {
        if (! array_key_exists($name, $this->sources)) {
            throw new \LogicException(sprintf("No such source '%s', registered sources are {%s}", $name, join(", ", array_keys($this->sources))));
        }

        return $this->sources[$name];
    }
}
