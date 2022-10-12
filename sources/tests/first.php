<?php declare(strict_types=1);

use PommProject\Foundation\Pomm;

use Chanmix51\NewModel\Structure;
use PommProject\Foundation\ResultIterator;
use PommProject\Foundation\Where;

$loader = require dirname(dirname(__DIR__)) . "/vendor/autoload.php";

/*
* Structure: a database structure, most likely a table
* DataSource : a data from the database, most likely a table but could be a view, function etc.
* Projection : a projection 
* Provider: a Projection combined with a DataSource
*/

class Entity {
    public int $id;
    public string $name;
    public \DateTime $created_at;
}

class EntityTable implements Projection {
    use Projection;

    // needed because Projection
    public function getDefinition(): String
    {
        return "public.test_entity";
    }

    // needed because projection
    public function getProjection(): Projection 
    {
        return Projection::fromStructure(new EntityStructure());
    }

}
class EntityProvider implements Provider, Projection {
    use Provider;
    use Projection;
    
    private Projection $source;

    public function __construct()
    {
        $this->source = new EntityTable;
    }

    // needed because Provider
    public function find(Where $where):  ResultIterator 
    {
        $sql = $this->getDefinition($where);
        
        return $this->getSession()->getQueryManager()
            ->query($sql, $where->getParameters());
    }

    // needed because Projection
    public function getDefinition(Where $where): string {
        $sql = "select {:projection:} from {:relation:} as entity where {:condition:}";
        $relation = new EntityTable();
        $sql = strtr($sql,
        [
            "{:projection:}" => $this->getProjection(),
            "{:relation:}" => $relation->getDefinition(),
            "{:condition}" => $where,
        ]);

        return $sql;
    }

    public function getProjection(): Projection {
        return $this->source->getProjection();
    }

}

$pomm = new Pomm(['my_database' => ['dsn' => 'pgsql://greg@postgres/greg', 'class:session_builder' => '\Chanmix51\NewModel\SessionBuilder']]);
$session = $pomm['my_database'];
$session->getProvider('')

