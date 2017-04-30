<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170429114209 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE lastposts (id INT AUTO_INCREMENT NOT NULL, thread INT DEFAULT NULL, post INT DEFAULT NULL, INDEX IDX_43530EBD31204C83 (thread), INDEX IDX_43530EBD5A8A6C8D (post), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lastposts ADD CONSTRAINT FK_43530EBD31204C83 FOREIGN KEY (thread) REFERENCES threads (number)');
        $this->addSql('ALTER TABLE lastposts ADD CONSTRAINT FK_43530EBD5A8A6C8D FOREIGN KEY (post) REFERENCES posts (post)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE lastposts');
    }
}
