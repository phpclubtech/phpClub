<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170403175937 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE threads MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE threads DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE posts MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE posts DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE threads DROP id, ADD PRIMARY KEY (number)');
        $this->addSql('ALTER TABLE posts DROP id, ADD PRIMARY KEY (post)');

        $this->addSql('ALTER TABLE posts ADD CONSTRAINT ManyToOnePosts FOREIGN KEY (thread) REFERENCES threads(number)');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT ManyToOneFiles FOREIGN KEY (post) REFERENCES posts(post)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY ManyToOneFiles');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY ManyToOnePosts');

        $this->addSql('ALTER TABLE threads DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE posts DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE posts ADD id INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE threads ADD id INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id)');
    }
}
