<?php declare(strict_types=1);

use Chanmix51\NewModel\Entity;
use PommProject\Foundation\Pomm;
use PommProject\Foundation\ResultIterator;
use PommProject\Foundation\Where;

use Chanmix51\NewModel\Provider;
use Chanmix51\NewModel\ProviderImplementation;

$loader = require dirname(dirname(__DIR__)) . "/vendor/autoload.php";

/*
* Structure: a database structure, most likely a table
* DataSource : a data from the database, most likely a table but could be a view, function etc.
* Projection : a projection 
* Provider: a Projection combined with a DataSource
*/

class ThingEntity implements Entity {
    public int $id;
    public string $name;
    public \DateTime $created_at;

    public static function hydrate(array $value): Entity
    {
        return new Self($value['id'], $value['name'], $value['created_at']);
    }
}



class ThingProvider implements Provider {
    
    use ProviderImplementation;

    public function findWhere(Where $where): ResultIterator
    {
        $sql = "select {:projection:} from {:source:} as entity where {:condition:}";
        $sql = strtr($sql, [
            "{:projection:}" => $this->getProjectionMap()->expand("entity"),
            "{:source:}" => "public.entity",
            "{:condition:}" => $where,
        ]);

        return $this->getSession()
            ->getQueryManager()
            ->query($sql, $where->getValues());
    }
}


$pomm = new Pomm(['my_database' => ['dsn' => 'pgsql://greg@postgres/greg', 'class:session_builder' => '\Chanmix51\NewModel\SessionBuilder']]);
$session = $pomm['my_database'];
$result = $session->getProvider('\ThingProvider')->findWhere(new Where);

