<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260103201136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game RENAME INDEX game_search TO IDX_232B318C5E237E06');
        $this->addSql('DROP INDEX UNIQ_9DF41401BF396750 ON steam');
        $this->addSql('ALTER TABLE steam RENAME INDEX steam_search TO IDX_9DF414015E237E06');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game RENAME INDEX idx_232b318c5e237e06 TO GAME_SEARCH');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9DF41401BF396750 ON steam (id)');
        $this->addSql('ALTER TABLE steam RENAME INDEX idx_9df414015e237e06 TO STEAM_SEARCH');
    }
}
