<?php declare(strict_types=1);

namespace DoctrineORMModule\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180914160637 extends AbstractMigration
{
    public function getDescription()
    {
        $description = 'This is the migration for Reset Password Key';
        return $description;
    }
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $alterTableVisitor = <<<SQL
            CREATE TABLE `reset_password_request` (
            `uuid` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
            `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `expired_at` datetime NOT NULL,
            `reseted_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            `deleted_at` datetime DEFAULT NULL,
            PRIMARY KEY (`uuid`),
            KEY `IDX_B9983CE5E7927C74` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci            
SQL;

        $this->addsql($alterTableVisitor);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $alterTableVisitor = <<<SQL
            DROP TABLE `reset_password_request` 
SQL;

        $this->addsql($alterTableVisitor);
    }
}