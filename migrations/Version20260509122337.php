<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509122337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_794381C6A76ED395E48FD905 ON review');
        $this->addSql('ALTER TABLE review ADD user_uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', DROP user_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_794381C6ABFE1C6FE48FD905 ON review (user_uuid, game_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_794381C6ABFE1C6FE48FD905 ON review');
        $this->addSql('ALTER TABLE review ADD user_id INT NOT NULL, DROP user_uuid');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_794381C6A76ED395E48FD905 ON review (user_id, game_id)');
    }
}
