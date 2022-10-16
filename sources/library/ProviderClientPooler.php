<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

use PommProject\Foundation\Client\ClientPooler;

class ProviderClientPooler extends ClientPooler {
    public function getPoolerType()
    {
        return 'provider';
    }

    public function createClient($identifier) {
        $provider = new $identifier;

        return $provider;
    }
}
