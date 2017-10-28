<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171028202539 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE file ADD thumb_remote_url VARCHAR(255) DEFAULT NULL, ADD client_name VARCHAR(255) DEFAULT NULL, DROP original_name, DROP relative_path, DROP thumbnail_relative_path, DROP thumbnail_remote_url');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE file ADD original_name VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD relative_path VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD thumbnail_relative_path VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD thumbnail_remote_url VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, DROP thumb_remote_url, DROP client_name');
    }
}
