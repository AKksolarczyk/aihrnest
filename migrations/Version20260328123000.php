<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add HRnest employee id to workspace users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspace_users ADD hrnest_employee_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_workspace_users_hrnest_employee_id ON workspace_users (hrnest_employee_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_workspace_users_hrnest_employee_id');
        $this->addSql('ALTER TABLE workspace_users DROP hrnest_employee_id');
    }
}
