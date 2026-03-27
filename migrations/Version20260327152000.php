<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add activation fields to workspace users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspace_users ADD is_active BOOLEAN DEFAULT TRUE NOT NULL');
        $this->addSql('ALTER TABLE workspace_users ADD email_confirmation_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_USERS_EMAIL_CONFIRMATION_TOKEN ON workspace_users (email_confirmation_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_WORKSPACE_USERS_EMAIL_CONFIRMATION_TOKEN');
        $this->addSql('ALTER TABLE workspace_users DROP is_active');
        $this->addSql('ALTER TABLE workspace_users DROP email_confirmation_token');
    }
}
