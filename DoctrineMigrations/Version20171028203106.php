<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171028203106 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE file ADD file_reference_file_path VARCHAR(255) NOT NULL, ADD file_reference_thumb_path VARCHAR(255) NOT NULL, DROP remote_url, DROP thumb_remote_url');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE file ADD remote_url VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, ADD thumb_remote_url VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, DROP file_reference_file_path, DROP file_reference_thumb_path');
    }
}
