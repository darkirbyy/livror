<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250105092028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game ADD release_year INT DEFAULT NULL, DROP release_date');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318CF3FD4ECA ON game (steam_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C5E237E06 ON game (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_232B318CF3FD4ECA ON game');
        $this->addSql('DROP INDEX UNIQ_232B318C5E237E06 ON game');
        $this->addSql('ALTER TABLE game ADD release_date DATE DEFAULT NULL, DROP release_year');
    }
}
