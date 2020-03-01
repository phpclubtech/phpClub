<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200301111237 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ref_link DROP FOREIGN KEY FK_21DFD8761645DEA9');
        $this->addSql('ALTER TABLE ref_link CHANGE reference_id reference_id INT NOT NULL');
        $this->addSql('ALTER TABLE 
          ref_link 
        ADD 
          CONSTRAINT FK_21DFD8761645DEA9 FOREIGN KEY (reference_id) REFERENCES post (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ref_link DROP FOREIGN KEY FK_21DFD8761645DEA9');
        $this->addSql('ALTER TABLE ref_link CHANGE reference_id reference_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE 
          ref_link 
        ADD 
          CONSTRAINT FK_21DFD8761645DEA9 FOREIGN KEY (reference_id) REFERENCES post (id)');
    }
}
