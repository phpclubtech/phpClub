<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170402190438 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE posts ADD id INT AUTO_INCREMENT NOT NULL, CHANGE thread thread INT NOT NULL, CHANGE post post INT NOT NULL, CHANGE date date VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE subject subject VARCHAR(255) NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE files ADD id INT AUTO_INCREMENT NOT NULL, CHANGE post post INT NOT NULL, CHANGE displayname displayname VARCHAR(255) NOT NULL, CHANGE duration duration TIME NOT NULL, CHANGE fullname fullname VARCHAR(255) NOT NULL, CHANGE height height INT NOT NULL, CHANGE md5 md5 VARCHAR(255) NOT NULL, CHANGE name name VARCHAR(255) NOT NULL, CHANGE nsfw nsfw INT NOT NULL, CHANGE size size INT NOT NULL, CHANGE tn_height tn_height INT NOT NULL, CHANGE tn_width tn_width INT NOT NULL, CHANGE type type INT NOT NULL, CHANGE width width INT NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE threads ADD id INT AUTO_INCREMENT NOT NULL, CHANGE number number INT NOT NULL, ADD PRIMARY KEY (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE files MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE files DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE files DROP id, CHANGE post post INT DEFAULT NULL, CHANGE displayname displayname VARCHAR(64) NOT NULL COLLATE utf8_unicode_ci, CHANGE duration duration TIME DEFAULT NULL, CHANGE fullname fullname VARCHAR(128) NOT NULL COLLATE utf8_unicode_ci, CHANGE height height INT DEFAULT NULL, CHANGE md5 md5 VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci, CHANGE name name VARCHAR(128) NOT NULL COLLATE utf8_unicode_ci, CHANGE nsfw nsfw INT DEFAULT NULL, CHANGE size size INT DEFAULT NULL, CHANGE tn_height tn_height INT DEFAULT NULL, CHANGE tn_width tn_width INT DEFAULT NULL, CHANGE type type INT DEFAULT NULL, CHANGE width width INT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE posts DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE posts DROP id, CHANGE thread thread INT DEFAULT NULL, CHANGE post post INT DEFAULT NULL, CHANGE date date VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci, CHANGE email email VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci, CHANGE name name VARCHAR(32) NOT NULL COLLATE utf8_unicode_ci, CHANGE subject subject VARCHAR(64) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE threads MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE threads DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE threads DROP id, CHANGE number number INT DEFAULT NULL');
    }
}
