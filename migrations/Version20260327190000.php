<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add locale to workspace users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE workspace_users ADD locale VARCHAR(5) DEFAULT 'pl' NOT NULL");
        $this->addSql("UPDATE workspace_users SET locale = 'pl' WHERE locale = '' OR locale IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspace_users DROP locale');
    }
}
