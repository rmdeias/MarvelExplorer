<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250919120616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_937AB03448CA5102 ON `character` (marvel_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5B7EA5AA48CA5102 ON comic (marvel_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BC06EA6348CA5102 ON creator (marvel_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AA3A933448CA5102 ON serie (marvel_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_937AB03448CA5102 ON `character`');
        $this->addSql('DROP INDEX UNIQ_AA3A933448CA5102 ON `serie`');
        $this->addSql('DROP INDEX UNIQ_BC06EA6348CA5102 ON `creator`');
        $this->addSql('DROP INDEX UNIQ_5B7EA5AA48CA5102 ON `comic`');
    }
}
