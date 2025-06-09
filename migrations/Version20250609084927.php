<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250609084927 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, game_id INT NOT NULL, date_add DATETIME NOT NULL, date_update DATETIME NOT NULL, comment LONGTEXT NOT NULL, first_play DATETIME DEFAULT NULL, hour_spend INT DEFAULT NULL, mark INT DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_794381C6E48FD905 (game_id), UNIQUE INDEX UNIQ_794381C6A76ED395E48FD905 (user_id, game_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE review ADD CONSTRAINT FK_794381C6E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE review DROP FOREIGN KEY FK_794381C6E48FD905
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE review
        SQL);
    }
}
