<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create waitlist, recurring desk reservations and issue reports tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workspace_desk_waitlist_entries (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) NOT NULL, desk_id VARCHAR(32) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_DESK_WAITLIST_USER_ID ON workspace_desk_waitlist_entries (user_id)');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_DESK_WAITLIST_DATE ON workspace_desk_waitlist_entries (date)');
        $this->addSql('CREATE TABLE workspace_recurring_desk_reservations (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) NOT NULL, desk_id VARCHAR(32) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, weekdays JSON NOT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_RECURRING_DESK_USER_ID ON workspace_recurring_desk_reservations (user_id)');
        $this->addSql('CREATE TABLE workspace_issue_reports (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) NOT NULL, desk_id VARCHAR(32) DEFAULT NULL, room_id VARCHAR(32) DEFAULT NULL, category VARCHAR(32) NOT NULL, description TEXT NOT NULL, status VARCHAR(16) NOT NULL, reported_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_ISSUE_REPORTS_USER_ID ON workspace_issue_reports (user_id)');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_ISSUE_REPORTS_STATUS ON workspace_issue_reports (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workspace_issue_reports');
        $this->addSql('DROP TABLE workspace_recurring_desk_reservations');
        $this->addSql('DROP TABLE workspace_desk_waitlist_entries');
    }
}
