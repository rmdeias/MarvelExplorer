<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917132157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE serie_creator DROP FOREIGN KEY FK_889F99C461220EA6');
        $this->addSql('ALTER TABLE serie_creator DROP FOREIGN KEY FK_889F99C4D94388BD');
        $this->addSql('ALTER TABLE creator_comic DROP FOREIGN KEY FK_D46DB31461220EA6');
        $this->addSql('ALTER TABLE creator_comic DROP FOREIGN KEY FK_D46DB314D663094A');
        $this->addSql('DROP TABLE serie_creator');
        $this->addSql('DROP TABLE creator_comic');
        $this->addSql('ALTER TABLE comic CHANGE variants variants JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE creator DROP role');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE serie_creator (serie_id INT NOT NULL, creator_id INT NOT NULL, INDEX IDX_889F99C461220EA6 (creator_id), INDEX IDX_889F99C4D94388BD (serie_id), PRIMARY KEY(serie_id, creator_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE creator_comic (creator_id INT NOT NULL, comic_id INT NOT NULL, INDEX IDX_D46DB31461220EA6 (creator_id), INDEX IDX_D46DB314D663094A (comic_id), PRIMARY KEY(creator_id, comic_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE serie_creator ADD CONSTRAINT FK_889F99C461220EA6 FOREIGN KEY (creator_id) REFERENCES creator (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE serie_creator ADD CONSTRAINT FK_889F99C4D94388BD FOREIGN KEY (serie_id) REFERENCES serie (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE creator_comic ADD CONSTRAINT FK_D46DB31461220EA6 FOREIGN KEY (creator_id) REFERENCES creator (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE creator_comic ADD CONSTRAINT FK_D46DB314D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE creator ADD role VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE comic CHANGE variants variants LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }
}
