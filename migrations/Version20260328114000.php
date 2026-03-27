<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328114000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add claim token fields to desk waitlist entries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspace_desk_waitlist_entries ADD claim_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace_desk_waitlist_entries ADD offered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_DESK_WAITLIST_CLAIM_TOKEN ON workspace_desk_waitlist_entries (claim_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_WORKSPACE_DESK_WAITLIST_CLAIM_TOKEN');
        $this->addSql('ALTER TABLE workspace_desk_waitlist_entries DROP claim_token');
        $this->addSql('ALTER TABLE workspace_desk_waitlist_entries DROP offered_at');
    }
}
