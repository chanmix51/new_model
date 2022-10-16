<?php declare(strict_types=1);
namespace Chanmix51\NewModel;

use PommProject\Foundation\Session\Session;
use PommProject\Foundation\SessionBuilder as FoundationSessionBuilder;

class SessionBuilder extends FoundationSessionBuilder {
    protected function postConfigure(Session $session)
    {
        parent::postConfigure($session);
        $session
            ->registerClientPooler(new ProviderClientPooler);
    
        return $this;
    }

}