<?php declare(strict_types = 1);

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171218202046 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE post CHANGE date date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE ref_link DROP FOREIGN KEY FK_21DFD8761645DEK9');
        $this->addSql('ALTER TABLE ref_link DROP FOREIGN KEY FK_21DFD8764B89032D');
        $this->addSql('ALTER TABLE ref_link ADD CONSTRAINT FK_21DFD8764B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ref_link ADD CONSTRAINT FK_21DFD8761645DEA9 FOREIGN KEY (reference_id) REFERENCES post (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE post CHANGE date date DATE NOT NULL');
        $this->addSql('ALTER TABLE ref_link DROP FOREIGN KEY FK_21DFD8764B89032C');
        $this->addSql('ALTER TABLE ref_link DROP FOREIGN KEY FK_21DFD8761645DEA9');
        $this->addSql('ALTER TABLE ref_link ADD CONSTRAINT FK_21DFD8761645DEK9 FOREIGN KEY (reference_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ref_link ADD CONSTRAINT FK_21DFD8764B89032D FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
    }
}
