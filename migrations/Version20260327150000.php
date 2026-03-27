<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add role to workspace users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE workspace_users ADD role VARCHAR(32) NOT NULL DEFAULT 'user'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspace_users DROP role');
    }
}
