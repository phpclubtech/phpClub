<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171112145709 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE last_post DROP FOREIGN KEY FK_CCA1A8D14B89032C');
        $this->addSql('ALTER TABLE last_post DROP FOREIGN KEY FK_CCA1A8D1E2904019');
        $this->addSql('ALTER TABLE last_post ADD CONSTRAINT FK_CCA1A8D14B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE last_post ADD CONSTRAINT FK_CCA1A8D1E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id) ON DELETE CASCADE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE last_post DROP FOREIGN KEY FK_CCA1A8D1E2904019');
        $this->addSql('ALTER TABLE last_post DROP FOREIGN KEY FK_CCA1A8D14B89032C');
        $this->addSql('ALTER TABLE last_post ADD CONSTRAINT FK_CCA1A8D1E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id)');
        $this->addSql('ALTER TABLE last_post ADD CONSTRAINT FK_CCA1A8D14B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
    }
}
