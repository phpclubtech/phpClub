<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171111191253 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE thread (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE last_post (thread_id INT NOT NULL, post_id INT NOT NULL, INDEX IDX_CCA1A8D1E2904019 (thread_id), INDEX IDX_CCA1A8D14B89032C (post_id), PRIMARY KEY(thread_id, post_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE archive_link (id INT AUTO_INCREMENT NOT NULL, thread_id INT DEFAULT NULL, link VARCHAR(255) NOT NULL, INDEX IDX_6894F6AE2904019 (thread_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE post (id INT NOT NULL, thread_id INT NOT NULL, text LONGTEXT NOT NULL, date DATE NOT NULL, email VARCHAR(255) DEFAULT NULL, is_op_post TINYINT(1) NOT NULL, is_first_post TINYINT(1) NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, INDEX IDX_5A8A6C8DE2904019 (thread_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ref_link (id INT AUTO_INCREMENT NOT NULL, post_id INT DEFAULT NULL, reference_id INT DEFAULT NULL, depth INT NOT NULL, INDEX IDX_21DFD8764B89032C (post_id), INDEX IDX_21DFD8761645DEA9 (reference_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE file (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, path VARCHAR(255) NOT NULL, thumb_path VARCHAR(255) NOT NULL, size INT DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, client_name VARCHAR(255) DEFAULT NULL, INDEX IDX_8C9F36104B89032C (post_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, salt VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE last_post ADD CONSTRAINT FK_CCA1A8D1E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id)');
        $this->addSql('ALTER TABLE last_post ADD CONSTRAINT FK_CCA1A8D14B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE archive_link ADD CONSTRAINT FK_6894F6AE2904019 FOREIGN KEY (thread_id) REFERENCES thread (id)');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DE2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE file ADD CONSTRAINT FK_8C9F36104B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE last_post DROP FOREIGN KEY FK_CCA1A8D1E2904019');
        $this->addSql('ALTER TABLE archive_link DROP FOREIGN KEY FK_6894F6AE2904019');
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DE2904019');
        $this->addSql('ALTER TABLE last_post DROP FOREIGN KEY FK_CCA1A8D14B89032C');
        $this->addSql('ALTER TABLE file DROP FOREIGN KEY FK_8C9F36104B89032C');
        $this->addSql('DROP TABLE thread');
        $this->addSql('DROP TABLE last_post');
        $this->addSql('DROP TABLE archive_link');
        $this->addSql('DROP TABLE post');
        $this->addSql('DROP TABLE ref_link');
        $this->addSql('DROP TABLE file');
        $this->addSql('DROP TABLE user');
    }
}
