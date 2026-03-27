<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create desk labels table and grant admin role to Piotr Nowak';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workspace_desk_labels (desk_id VARCHAR(32) NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(desk_id))');
        $this->addSql('UPDATE workspace_users SET roles = \'["ROLE_USER","ROLE_ADMIN"]\' WHERE email = \'piotr.nowak@example.com\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE workspace_users SET roles = \'["ROLE_USER"]\' WHERE email = \'piotr.nowak@example.com\'');
        $this->addSql('DROP TABLE workspace_desk_labels');
    }
}
