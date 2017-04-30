<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170429114012 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE refmap ADD depth INT NOT NULL, CHANGE post post INT DEFAULT NULL, CHANGE reference reference INT DEFAULT NULL');
        $this->addSql('ALTER TABLE refmap ADD CONSTRAINT FK_D45B2E225A8A6C8D FOREIGN KEY (post) REFERENCES posts (post)');
        $this->addSql('ALTER TABLE refmap ADD CONSTRAINT FK_D45B2E22AEA34913 FOREIGN KEY (reference) REFERENCES posts (post)');
        $this->addSql('CREATE INDEX IDX_D45B2E225A8A6C8D ON refmap (post)');
        $this->addSql('CREATE INDEX IDX_D45B2E22AEA34913 ON refmap (reference)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE refmap DROP FOREIGN KEY FK_D45B2E225A8A6C8D');
        $this->addSql('ALTER TABLE refmap DROP FOREIGN KEY FK_D45B2E22AEA34913');
        $this->addSql('DROP INDEX IDX_D45B2E225A8A6C8D ON refmap');
        $this->addSql('DROP INDEX IDX_D45B2E22AEA34913 ON refmap');
        $this->addSql('ALTER TABLE refmap DROP depth, CHANGE post post INT NOT NULL, CHANGE reference reference INT NOT NULL');
    }
}
