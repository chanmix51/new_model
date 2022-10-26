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

class TikOwnerTable implements SqlSource
{
    public function getStructure(): Structure {
        return (new Structure)
            ->setField("tik_owner_id", "integer")
            ->setField("name", "text")
            ->setField("created_at", "timestamptz");
    }

    public function getDefinition(): string
    {
        return "new_model_test.tik_owner";
    }
}

class TikTable implements SqlSource 
{
    public function getStructure(): Structure {
        return (new Structure)
            ->setField("tik_id", "integer")
            ->setField("tik_owner_id", "integer")
            ->setField("value", "integer")
            ->setField("created_at", "timestamptz");
    }

    public function getDefinition(): string
    {
        return "new_model_test.tik";
    }
}

class TikOwner implements Entity
{
    private function __construct(public int $tik_owner_id, public string $name, public \DateTime $created_at, public int $nb_tik, public int $tik_value_sum, public float $mean_tik_value) {}
    
    public static function hydrate(array $value): Entity
    {
        return new Self($value['tik_owner_id'], $value['name'], $value['created_at'], $value['nb_tik'], $value['tik_value_sum'], $value['mean_tik_value']);
    }
}

class TikOwnerProjectionMap implements ProjectionMap {
    use ProjectionMapImplementation;

    public function __construct() {
        $this->projection = ProjectionFieldDefinition::fromStructure("tik_owner", (new TikOwnerTable)->getStructure());
        $this->projection["nb_tik"] = new ProjectionFieldDefinition("tik", "count(**.*)", 'nb_tik', 'int');
        $this->projection["tik_value_sum"] = new ProjectionFieldDefinition("tik", "sum(**.tik_value)","tik_value_sum", 'int');
        $this->projection["mean_tik_value"] = new ProjectionFieldDefinition("tik", "avg(**.tik_value)","mean_tik_value", 'float');
    }

}

class TikProvider implements Provider
{
    use ProviderImplementation;

    public function findWhere(Where $where = new Where): ResultIterator
    {
        $sql = <<<SQL
select {:projection:}
from {:tik_owner:} as tik_owner
  left outer join {:tik:} as tik using (tik_owner_id)
where {:where:}
group by tik_owner_id
order by tik_owner_id asc
SQL;
        $sql = strtr($sql, [
            "{:projection:}" => $this->getProjectionMap()->expand(["tik_owner" => "tik_owner", "tik" => "tik"]),
            "{:tik_owner:}" => $this->getSource('tik_owner')->getDefinition(),
            "{:tik:}" => $this->getSource('tik')->getDefinition(),
            "{:where:}" => $where,
        ]);

        return $this->query($sql, $where->getValues());
    }

    public function getEntityType(): string
    {
        return TikOwner::class;
    }

    public function initialize(Session $session)
    {
        $this->session = $session;
        $this->projection = new TikOwnerProjectionMap;
        $this->sources = ["tik_owner" => new TikOwnerTable, "tik" => new TikTable];
    }
}

// setup
$setup_sql = [
    "drop schema if exists new_model_test cascade",
    "create schema new_model_test",
    "create table new_model_test.tik_owner (tik_owner_id serial primary key, name text not null, created_at timestamptz not null default now())",
    "insert into new_model_test.tik_owner (name) values ('pika'), ('chu')",
    "create table new_model_test.tik as select tik_id as tik_id, floor(random() * 2)::int + 1 as tik_owner_id, floor(random() * 100)::int as tik_value from generate_series(1, 100) as pika(tik_id);",
    "alter table new_model_test.tik add foreign key (tik_owner_id) references new_model_test.tik_owner (tik_owner_id)",
];
$pomm = new Pomm(['my_database' => ['dsn' => 'pgsql://greg@postgres/greg', 'class:session_builder' => '\Chanmix51\NewModel\SessionBuilder']]);
$session = $pomm['my_database'];

foreach ($setup_sql as $query) {
    printf("query => '%s'\n", $query);
    $session->getConnection()->executeAnonymousQuery($query);
}
// actual test
printf("\nPROVIDER TEST RESULTS\n");

$result = $session->getProvider(TikProvider::class)->findWhere();

if ($result->isEmpty()) {
    printf("No results\n");
} else {
    foreach ($result as $tik) {
        print_r($tik);
    }
}