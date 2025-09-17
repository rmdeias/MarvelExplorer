<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917081114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE creator (id INT AUTO_INCREMENT NOT NULL, marvel_id INT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, modified VARCHAR(255) NOT NULL, thumbnail VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE creator_comic (creator_id INT NOT NULL, comic_id INT NOT NULL, INDEX IDX_D46DB31461220EA6 (creator_id), INDEX IDX_D46DB314D663094A (comic_id), PRIMARY KEY(creator_id, comic_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE creator_comic ADD CONSTRAINT FK_D46DB31461220EA6 FOREIGN KEY (creator_id) REFERENCES creator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE creator_comic ADD CONSTRAINT FK_D46DB314D663094A FOREIGN KEY (comic_id) REFERENCES comic (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE creator_comic DROP FOREIGN KEY FK_D46DB31461220EA6');
        $this->addSql('ALTER TABLE creator_comic DROP FOREIGN KEY FK_D46DB314D663094A');
        $this->addSql('DROP TABLE creator');
        $this->addSql('DROP TABLE creator_comic');
    }
}
