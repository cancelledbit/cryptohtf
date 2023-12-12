<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231212090130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE personal_vault_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE personal_vault (id INT NOT NULL, owner_id INT NOT NULL, mount_point VARCHAR(255) DEFAULT NULL, cypher_point VARCHAR(255) NOT NULL, last_mount_ts TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A7690F377E3C61F9 ON personal_vault (owner_id)');
        $this->addSql('ALTER TABLE personal_vault ADD CONSTRAINT FK_A7690F377E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE personal_vault_id_seq CASCADE');
        $this->addSql('ALTER TABLE personal_vault DROP CONSTRAINT FK_A7690F377E3C61F9');
        $this->addSql('DROP TABLE personal_vault');
    }
}
