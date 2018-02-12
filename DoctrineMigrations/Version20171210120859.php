<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171210120859 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE archive_link');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE archive_link (id INT AUTO_INCREMENT NOT NULL, thread_id INT DEFAULT NULL, link VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, INDEX IDX_6894F6AE2904019 (thread_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE archive_link ADD CONSTRAINT FK_6894F6AE2904019 FOREIGN KEY (thread_id) REFERENCES thread (id)');
    }
}
