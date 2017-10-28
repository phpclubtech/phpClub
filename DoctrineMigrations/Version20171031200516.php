<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171031200516 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX i1 ON post');
        $this->addSql('ALTER TABLE file ADD path VARCHAR(255) NOT NULL, ADD thumb_path VARCHAR(255) NOT NULL, DROP file_reference_file_path, DROP file_reference_thumb_path, CHANGE size size INT NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE file ADD file_reference_file_path VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, ADD file_reference_thumb_path VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, DROP path, DROP thumb_path, CHANGE size size INT DEFAULT NULL');
        $this->addSql('CREATE INDEX i1 ON post (thread_id)');
    }
}
