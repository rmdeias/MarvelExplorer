<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917083747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE serie (id INT AUTO_INCREMENT NOT NULL, marvel_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, start_year VARCHAR(255) NOT NULL, end_year VARCHAR(255) NOT NULL, thumbnail VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE serie_creator (serie_id INT NOT NULL, creator_id INT NOT NULL, INDEX IDX_889F99C4D94388BD (serie_id), INDEX IDX_889F99C461220EA6 (creator_id), PRIMARY KEY(serie_id, creator_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE serie_character (serie_id INT NOT NULL, character_id INT NOT NULL, INDEX IDX_BB308E05D94388BD (serie_id), INDEX IDX_BB308E051136BE75 (character_id), PRIMARY KEY(serie_id, character_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE serie_creator ADD CONSTRAINT FK_889F99C4D94388BD FOREIGN KEY (serie_id) REFERENCES serie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE serie_creator ADD CONSTRAINT FK_889F99C461220EA6 FOREIGN KEY (creator_id) REFERENCES creator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE serie_character ADD CONSTRAINT FK_BB308E05D94388BD FOREIGN KEY (serie_id) REFERENCES serie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE serie_character ADD CONSTRAINT FK_BB308E051136BE75 FOREIGN KEY (character_id) REFERENCES `character` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comic ADD serie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE comic ADD CONSTRAINT FK_5B7EA5AAD94388BD FOREIGN KEY (serie_id) REFERENCES serie (id)');
        $this->addSql('CREATE INDEX IDX_5B7EA5AAD94388BD ON comic (serie_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comic DROP FOREIGN KEY FK_5B7EA5AAD94388BD');
        $this->addSql('ALTER TABLE serie_creator DROP FOREIGN KEY FK_889F99C4D94388BD');
        $this->addSql('ALTER TABLE serie_creator DROP FOREIGN KEY FK_889F99C461220EA6');
        $this->addSql('ALTER TABLE serie_character DROP FOREIGN KEY FK_BB308E05D94388BD');
        $this->addSql('ALTER TABLE serie_character DROP FOREIGN KEY FK_BB308E051136BE75');
        $this->addSql('DROP TABLE serie');
        $this->addSql('DROP TABLE serie_creator');
        $this->addSql('DROP TABLE serie_character');
        $this->addSql('DROP INDEX IDX_5B7EA5AAD94388BD ON comic');
        $this->addSql('ALTER TABLE comic DROP serie_id');
    }
}
