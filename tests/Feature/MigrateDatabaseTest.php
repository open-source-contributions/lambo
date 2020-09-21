<?php

namespace Tests\Feature;

use App\Actions\MigrateDatabase;
use App\Tools\Database;
use Tests\Feature\Fakes\FakeProcess;
use Tests\TestCase;

class MigrateDatabaseTest extends TestCase
{
    private $database;

    public function setUp(): void
    {
        parent::setUp();
        $this->database = $this->mock(Database::class);
    }

    /** @test */
    function it_migrates_the_database()
    {
        config(['lambo.store.database_host' => 'example.test']);
        config(['lambo.store.database_port' => 3306]);
        config(['lambo.store.database_username' => 'user']);
        config(['lambo.store.database_password' => 'password']);
        config(['lambo.store.database_name' => 'foo']);

        $this->database->shouldReceive('url')
            ->with('mysql://user:password@example.test:3306')
            ->once()
            ->andReturnSelf();

        $this->database->shouldReceive('find')
            ->once()
            ->andReturnTrue();

        $this->shell->shouldReceive('execInProject')
            ->with('php artisan migrate --quiet')
            ->once()
            ->andReturn(FakeProcess::success());

        app(MigrateDatabase::class)();
    }

    /** @test */
    function failed_migrations_do_not_halt_execution()
    {
        config(['lambo.store.database_host' => 'example.test']);
        config(['lambo.store.database_port' => 3306]);
        config(['lambo.store.database_username' => 'user']);
        config(['lambo.store.database_password' => 'password']);
        config(['lambo.store.database_name' => 'foo']);

        $this->database->shouldReceive('url')
            ->with('mysql://user:password@example.test:3306')
            ->once()
            ->andReturnSelf();

        $this->database->shouldReceive('find')
            ->once()
            ->andReturnTrue();

        $this->shell->shouldReceive('execInProject')
            ->with('php artisan migrate --quiet')
            ->once()
            ->andReturn(FakeProcess::fail('php artisan migrate --quiet'));

        app(MigrateDatabase::class)();
    }
}
