<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/dbal-types.php';

$postgresqlPlatformClass = require __DIR__ . '/../config/postgresql-platform.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$dbConfig = require __DIR__ . '/../config/database.php';
$connectionParams = $dbConfig['connections']['postgresql'];

$connectionParams['host'] = $_ENV['SERVER_NAME'];
$connectionParams['port'] = $_ENV['DB_EXTERNAL_PORT'];

$connection = DriverManager::getConnection([
    'driver' => 'pdo_pgsql',
    'host' => $connectionParams['host'],
    'port' => $connectionParams['port'],
    'dbname' => $connectionParams['database'],
    'user' => $connectionParams['username'],
    'password' => $connectionParams['password'],
    'charset' => $connectionParams['charset'],
    'options' => $connectionParams['options'],
    'platform' => new $postgresqlPlatformClass(),
]);

$migrationsConfig = require __DIR__ . '/../config/migrations.php';

$config = new Doctrine\Migrations\Configuration\Configuration();
$config->addMigrationsDirectory(
    $migrationsConfig['migrations']['namespace'],
    $migrationsConfig['migrations']['path']
);
$config->setAllOrNothing(false);
$config->setCheckDatabasePlatform(true);

$dependencyFactory = DependencyFactory::fromConnection(
    new ExistingConfiguration($config),
    new ExistingConnection($connection)
);

$commandName = $argv[1] ?? 'help';

$commandMap = [
    'migrations:status' => Command\StatusCommand::class,
    'migrations:migrate' => Command\MigrateCommand::class,
    'migrations:generate' => Command\GenerateCommand::class,
    'migrations:diff' => Command\DiffCommand::class,
    'migrations:execute' => Command\ExecuteCommand::class,
    'migrations:list' => Command\ListCommand::class,
    'migrations:version' => Command\VersionCommand::class,
    'migrations:up-to-date' => Command\UpToDateCommand::class,
    'migrations:sync-metadata-storage' => Command\SyncMetadataCommand::class,
];

if (!isset($commandMap[$commandName])) {
    echo "Available commands:\n";
    foreach (array_keys($commandMap) as $cmd) {
        echo "  php bin/migrate-host.php $cmd\n";
    }
    exit(1);
}

$commandClass = $commandMap[$commandName];
$command = new $commandClass($dependencyFactory);

array_shift($argv);

$command->run(new ArgvInput($argv), new ConsoleOutput());
