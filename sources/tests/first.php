<?php declare(strict_types=1);
namespace Chanmix51\NewModel\Test;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\Session\Session;
use PommProject\Foundation\Where;

use Chanmix51\NewModel\Entity;
use Chanmix51\NewModel\ProjectionFieldDefinition;
use Chanmix51\NewModel\ResultIterator;
use Chanmix51\NewModel\ProjectionMap;
use Chanmix51\NewModel\ProjectionMapImplementation;
use Chanmix51\NewModel\Provider;
use Chanmix51\NewModel\ProviderImplementation;
use Chanmix51\NewModel\SqlSource;
use Chanmix51\NewModel\Structure;

$loader = require dirname(dirname(__DIR__)) . "/vendor/autoload.php";

/*
* Structure: a database structure, most likely a table
* DataSource : a data from the database, most likely a table but could be a view, function etc.
* Projection : a projection 
* Provider: a Projection combined with a DataSource
*/

class ThingEntity implements Entity {

    private function __construct(public int $id, public string $name, public \DateTime $created_at) {}

    /// see Entity
    public static function hydrate(array $value): Entity
    {
        return new Self($value['id'], $value['name'], $value['created_at']);
    }
}

class ThingProjectionMap implements ProjectionMap {
    use ProjectionMapImplementation;

    public function __construct() {
        $this->projection = ProjectionFieldDefinition::fromStructure("thing", (new ThingTable)->getStructure());
    }
}

class ThingTable implements SqlSource {
    public function getStructure(): Structure {
        return (new Structure)
            ->setField("id", "integer")
            ->setField("name", "text")
            ->setField("created_at", "timestamptz");
    }

    public function getDefinition(): string {
        return "first_test.thing";
    }
}

class ThingProvider implements Provider {
    
    use ProviderImplementation ;

    public function findWhere(Where $where = new Where): ResultIterator
    {
        $sql = "select {:projection:} from {:source:} as thing where {:condition:}";
        $sources_alias = ['thing' => 'thing'];
        $sql = strtr($sql, [
            "{:projection:}" => $this->getProjectionMap()->expand($sources_alias),
            "{:source:}" => $this->getSource('thing')->getDefinition(),
            "{:condition:}" => $where,
        ]);

        return $this->query($sql, $where->getValues());
    }

    public function getEntityType(): string
    {
        return ThingEntity::class;
    }

    public function initialize(Session $session)
    {
        $this->session = $session;
        $this->projection = new ThingProjectionMap;
        $this->sources = ["thing" => new ThingTable];
    }
}


// setup
$setup_sql = [
    "drop schema if exists first_test cascade",
    "create schema first_test",
    "create table first_test.thing (id serial primary key, name text not null, created_at timestamptz not null default now())",
    "insert into first_test.thing (name) values ('pika'), ('chu')",
];
$pomm = new Pomm(['my_database' => ['dsn' => 'pgsql://greg@postgres/greg', 'class:session_builder' => '\Chanmix51\NewModel\SessionBuilder']]);
$session = $pomm['my_database'];

foreach ($setup_sql as $query) {
    printf("query => '%s'\n", $query);
    $session->getConnection()->executeAnonymousQuery($query);
}
// actual test
printf("\nPROVIDER TEST RESULTS\n");

$result = $session->getProvider(ThingProvider::class)->findWhere();

if ($result->isEmpty()) {
    printf("No results\n");
} else {
    foreach ($result as $thing) {
        print_r($thing);
    }
}