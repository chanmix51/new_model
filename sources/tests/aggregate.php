<?php declare(strict_types=1);
namespace Chanmix51\NewModel\Test;

use Chanmix51\NewModel\Entity;
use Chanmix51\NewModel\ProjectionMap;
use Chanmix51\NewModel\Provider;
use Chanmix51\NewModel\ProjectionFieldDefinition;
use Chanmix51\NewModel\ProjectionMapImplementation;
use Chanmix51\NewModel\ProviderImplementation;
use Chanmix51\NewModel\ResultIterator;
use Chanmix51\NewModel\SqlSource;
use Chanmix51\NewModel\Structure;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\Session\Session;
use PommProject\Foundation\Where;

$loader = require dirname(dirname(__DIR__)) . "/vendor/autoload.php";

/*
 * Test scenario:
 * Show a short view of a Thing.
 * Each Thing has an Author and may have multiple Comments. In this case we want
 * to display the associated ShortAuthor and the last 5 Comments if any.
 */

 /*
  * THING
  */
class ThingTable implements SqlSource {
    public function getStructure(): Structure {
        return (new Structure)
            ->setField("thing_id", "integer")
            ->setField("name", "text")
            ->setField("author_id", "int")
            ->setField("title", "text")
            ->setField("content", "text")
            ->setField("created_at", "timestamptz");
    }

    public function getDefinition(): string {
        return "aggregate_test.thing";
    }
}

class ShortThingEntity implements Entity {

    private function __construct(
        public int $thing_id,
        public string $name,
        public string $title,
        public \DateTime $created_at,
        public ShortAuthor $short_author,
        public array $last_comments,
        ) {}

    /// see Entity
    public static function hydrate(array $value): Entity
    {
        extract($value);

        return new Self(
            $thing_id,
            $name,
            $title,
            $created_at,
            $short_author,
            $last_comments
        );
    }
}

class ShortThingProjectionMap implements ProjectionMap {
    use ProjectionMapImplementation;

    public function __construct() {
        $this->projection = ProjectionFieldDefinition::fromStructure("thing", (new ThingTable)->getStructure());
        unset($this->projection['content']);
        $this
            ->addField("short_author", "**", "short_author", "author")
            ->addField("short_comment", "array_agg(**)", "last_comments", "comment[]");
    }
}

/*
 * AUTHOR & SHORTAUTHOR
 */
class AuthorTable implements SqlSource {
    public function getStructure(): Structure {
        return (new Structure)
            ->setField("author_id", "integer")
            ->setField("name", "text")
            ->setField("email", "text");
    }

    public function getDefinition(): string {
        return "aggregate_test.author";
    }
}

class ShortAuthor implements Entity {
    private function __construct(
        public int $author_id,
        public string $name,
        ) {}

    public static function hydrate(array $value): Entity
    {
        return new Self($value['author_id'], $value['name']);
    }
}

class ShortAuthorProjectionMap implements ProjectionMap {
    use ProjectionMapImplementation;

    public function __construct() {
        $this->projection = ProjectionFieldDefinition::fromStructure("author", (new AuthorTable)->getStructure());
        unset($this->projection['email']);
    }
}

/*
 * COMMENT & SHORTCOMMENT
 */

class CommentTable implements SqlSource {
    public function getStructure(): Structure {
        return (new Structure)
            ->setField("comment_id", "integer")
            ->setField("thing_id", "integer")
            ->setField("content", "text")
            ->setField("created_at", "timestamptz");
    }

    public function getDefinition(): string {
        return "aggregate_test.comment";
    }
}

class ShortComment implements Entity {
    private function __construct(
        public int $comment_id,
        public int $author_id,
        public string $reduced_content,
        ) {}

    public static function hydrate(array $value): Entity
    {
        return new Self($value['comment_id'], $value['author_id'], $value['reduced_content']);
    }
}

class ShortCommentProjectionMap implements ProjectionMap {
    use ProjectionMapImplementation;

    public function __construct() {
        $this->projection = ProjectionFieldDefinition::fromStructure("comment", (new CommentTable)->getStructure());
        unset($this->projection['content']);
        $this->projection['reduced_content'] = new ProjectionFieldDefinition('comment', "left(**.content, 5) || '…'", 'reduced_content', 'text');
    }
}

// SHORT THING PROVIDER
class ShortThingProvider implements Provider {
    
    use ProviderImplementation;

    private ShortAuthorProjectionMap  $short_author_projection;
    private ShortCommentProjectionMap $short_comment_projection;

    public function getEntityType(): string
    {
        return 'Chanmix51\NewModel\Test\ShortThingEntity';
    }

    public function initialize(Session $session)
    {
        $this->session = $session;

        $this->sources['author'] = new AuthorTable;
        $this->sources['comment'] = new CommentTable;
        $this->sources['thing'] = new ThingTable;

        $this->short_author_projection = new ShortAuthorProjectionMap;
        $this->short_comment_projection = new ShortCommentProjectionMap;
        $this->projection = new ShortThingProjectionMap;
    }

    public function findWhere(Where $where = new Where): ResultIterator
    {
        $sql = <<<"SQL"
with
  short_author as (
    select
      {:short_author_projection:}
    from {:author:} as author
  ),
  short_comment as (
    select
      {:short_comment_projection:}
    from {:comment:} as comment
  )
select
  {:short_thing_projection:}
from {:thing:} as thing
  inner join short_author using (author_id)
  left join short_comment using (thing_id)
where {:condition:}
group by thing.thing_id, short_author.*  
SQL;
        $source_aliases = ["author" => "author", "comment" => "comment", "thing" => "thing", "short_author" => "short_author", "short_comment" => "short_comment"];
        $sql = strtr($sql, [
            '{:short_author_projection:}' => $this->short_author_projection->expand($source_aliases),
            '{:short_comment_projection:}' => $this->short_comment_projection->expand($source_aliases),
            '{:author:}' => $this->sources['author']->getDefinition(),
            '{:comment:}' => $this->sources['comment']->getDefinition(),
            '{:short_thing_projection:}' => $this->projection->expand($source_aliases),
            '{:thing:}' => $this->getSource('thing')->getDefinition(),
            '{:condition:}' => $where,
        ]);

        return $this->query($sql, $where->getValues());
    }
}

// setup
$setup_sql = [
    "drop schema if exists aggregate_test cascade",
    "create schema aggregate_test",
    "create table aggregate_test.author (author_id serial primary key, name text not null, email text not null)",
    "insert into aggregate_test.author (name, email) values ('one', 'one@internet.com'), ('two', 'two@internet.com')",
    "create table aggregate_test.thing (thing_id serial primary key, author_id int not null references aggregate_test.author (author_id), name text not null, title text not null, content text, created_at timestamptz not null default now())",
    "insert into aggregate_test.thing (author_id, name, title, content) values (1, 'pika', 'pika title', 'pika content'), (1, 'chu', 'chu title', 'chu content')",
    "create table aggregate_test.comment (comment_id serial primary key, author_id int not null references aggregate_test.author (author_id), thing_id int not null references aggregate_test.thing (thing_id), content text, created_at timestamptz not null default now())",
    "insert into aggregate_test.comment (author_id, thing_id, content) values (2, 1, 'comment 1 on thing 1'), (1, 1, 'response on comment 1 by author 1')",
];
$pomm = new Pomm(['my_database' => ['dsn' => 'pgsql://greg@postgres/greg', 'class:session_builder' => '\Chanmix51\NewModel\SessionBuilder']]);
$session = $pomm['my_database'];

foreach ($setup_sql as $query) {
    printf("query => '%s'\n", $query);
    $session->getConnection()->executeAnonymousQuery($query);
}
// actual test
printf("\nPROVIDER TEST RESULTS\n");

$result = $session->getProvider(ShortThingProvider::class)->findWhere();

if ($result->isEmpty()) {
    printf("No results\n");
} else {
    foreach ($result as $thing) {
        print_r($thing);
    }
}