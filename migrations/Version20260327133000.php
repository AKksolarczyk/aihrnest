<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workspace tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workspace_users (id VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, team VARCHAR(255) NOT NULL, assigned_desk_id VARCHAR(32) NOT NULL, schedule JSON NOT NULL, vacation_days_total INT NOT NULL, vacation_days_remaining INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE workspace_vacations (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_VACATIONS_USER_ID ON workspace_vacations (user_id)');
        $this->addSql('CREATE TABLE workspace_desk_claims (id VARCHAR(32) NOT NULL, user_id VARCHAR(32) NOT NULL, desk_id VARCHAR(32) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_WORKSPACE_DESK_CLAIMS_USER_ID ON workspace_desk_claims (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workspace_desk_claims');
        $this->addSql('DROP TABLE workspace_vacations');
        $this->addSql('DROP TABLE workspace_users');
    }
}
