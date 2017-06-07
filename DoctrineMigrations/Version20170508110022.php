<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170508110022 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE refmap (id INT AUTO_INCREMENT NOT NULL, post INT DEFAULT NULL, reference INT DEFAULT NULL, depth INT NOT NULL, INDEX IDX_D45B2E225A8A6C8D (post), INDEX IDX_D45B2E22AEA34913 (reference), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE archivelinks (id INT AUTO_INCREMENT NOT NULL, thread INT DEFAULT NULL, link VARCHAR(255) NOT NULL, INDEX IDX_BC82079831204C83 (thread), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE threads (number INT NOT NULL, PRIMARY KEY(number)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts (post INT NOT NULL, thread INT DEFAULT NULL, comment LONGTEXT NOT NULL, date VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, INDEX IDX_885DBAFA31204C83 (thread), PRIMARY KEY(post)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, salt VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lastposts (id INT AUTO_INCREMENT NOT NULL, thread INT DEFAULT NULL, post INT DEFAULT NULL, INDEX IDX_43530EBD31204C83 (thread), INDEX IDX_43530EBD5A8A6C8D (post), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE files (id INT AUTO_INCREMENT NOT NULL, post INT DEFAULT NULL, displayname VARCHAR(255) NOT NULL, duration TIME DEFAULT NULL, fullname VARCHAR(255) NOT NULL, height INT NOT NULL, md5 VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, nsfw INT NOT NULL, path VARCHAR(255) NOT NULL, size INT NOT NULL, thumbnail VARCHAR(255) NOT NULL, tn_height INT NOT NULL, tn_width INT NOT NULL, type INT NOT NULL, width INT NOT NULL, INDEX IDX_63540595A8A6C8D (post), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE refmap ADD CONSTRAINT FK_D45B2E225A8A6C8D FOREIGN KEY (post) REFERENCES posts (post)');
        $this->addSql('ALTER TABLE refmap ADD CONSTRAINT FK_D45B2E22AEA34913 FOREIGN KEY (reference) REFERENCES posts (post)');
        $this->addSql('ALTER TABLE archivelinks ADD CONSTRAINT FK_BC82079831204C83 FOREIGN KEY (thread) REFERENCES threads (number)');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA31204C83 FOREIGN KEY (thread) REFERENCES threads (number)');
        $this->addSql('ALTER TABLE lastposts ADD CONSTRAINT FK_43530EBD31204C83 FOREIGN KEY (thread) REFERENCES threads (number)');
        $this->addSql('ALTER TABLE lastposts ADD CONSTRAINT FK_43530EBD5A8A6C8D FOREIGN KEY (post) REFERENCES posts (post)');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_63540595A8A6C8D FOREIGN KEY (post) REFERENCES posts (post)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE archivelinks DROP FOREIGN KEY FK_BC82079831204C83');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFA31204C83');
        $this->addSql('ALTER TABLE lastposts DROP FOREIGN KEY FK_43530EBD31204C83');
        $this->addSql('ALTER TABLE refmap DROP FOREIGN KEY FK_D45B2E225A8A6C8D');
        $this->addSql('ALTER TABLE refmap DROP FOREIGN KEY FK_D45B2E22AEA34913');
        $this->addSql('ALTER TABLE lastposts DROP FOREIGN KEY FK_43530EBD5A8A6C8D');
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_63540595A8A6C8D');
        $this->addSql('DROP TABLE refmap');
        $this->addSql('DROP TABLE archivelinks');
        $this->addSql('DROP TABLE threads');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE lastposts');
        $this->addSql('DROP TABLE files');
    }
}
