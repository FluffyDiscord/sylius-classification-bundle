<?php

declare(strict_types=1);

namespace FluffyDiscord\SyliusClassificationBundle\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create classification axes, values, their translations and product-variant assignments.';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof PostgreSQLPlatform) {
            $this->createPostgreSqlSchema();

            return;
        }

        $this->abortIf(! $platform instanceof AbstractMySQLPlatform, 'Only PostgreSQL and MySQL/MariaDB are supported.');

        $this->createMySqlSchema();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sylius_classification_variant_value');
        $this->addSql('DROP TABLE sylius_classification_value_translation');
        $this->addSql('DROP TABLE sylius_classification_value');
        $this->addSql('DROP TABLE sylius_classification_translation');
        $this->addSql('DROP TABLE sylius_classification');

        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $this->addSql('DROP SEQUENCE sylius_classification_id_seq');
            $this->addSql('DROP SEQUENCE sylius_classification_translation_id_seq');
            $this->addSql('DROP SEQUENCE sylius_classification_value_id_seq');
            $this->addSql('DROP SEQUENCE sylius_classification_value_translation_id_seq');
            $this->addSql('DROP SEQUENCE sylius_classification_variant_value_id_seq');
        }
    }

    private function createPostgreSqlSchema(): void
    {
        $this->addSql('CREATE SEQUENCE sylius_classification_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE sylius_classification_translation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE sylius_classification_value_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE sylius_classification_value_translation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE sylius_classification_variant_value_id_seq INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE sylius_classification (id INT NOT NULL, code VARCHAR(64) NOT NULL, multiple BOOLEAN NOT NULL, position INT DEFAULT 0 NOT NULL, config JSONB DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_sylius_classification_code ON sylius_classification (code)');

        $this->addSql('CREATE TABLE sylius_classification_translation (id INT NOT NULL, translatable_id INT NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_sylius_classification_translation ON sylius_classification_translation (translatable_id)');
        $this->addSql('CREATE UNIQUE INDEX sylius_classification_translation_uniq_trans ON sylius_classification_translation (translatable_id, locale)');

        $this->addSql('CREATE TABLE sylius_classification_value (id INT NOT NULL, classification_id INT NOT NULL, code VARCHAR(96) NOT NULL, position INT DEFAULT 0 NOT NULL, config JSONB DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_sylius_classification_value_code ON sylius_classification_value (code)');
        $this->addSql('CREATE INDEX idx_sylius_classification_value_axis ON sylius_classification_value (classification_id)');

        $this->addSql('CREATE TABLE sylius_classification_value_translation (id INT NOT NULL, translatable_id INT NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_sylius_classification_value_translation ON sylius_classification_value_translation (translatable_id)');
        $this->addSql('CREATE UNIQUE INDEX sylius_classification_value_translation_uniq_trans ON sylius_classification_value_translation (translatable_id, locale)');

        $this->addSql('CREATE TABLE sylius_classification_variant_value (id INT NOT NULL, product_variant_id INT NOT NULL, classification_value_id INT NOT NULL, classification_id INT NOT NULL, position INT DEFAULT 0 NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX sylius_classification_variant_value_uniq ON sylius_classification_variant_value (product_variant_id, classification_value_id)');
        $this->addSql('CREATE INDEX sylius_classification_variant_value_value_idx ON sylius_classification_variant_value (classification_value_id)');
        $this->addSql('CREATE INDEX sylius_classification_variant_value_axis_idx ON sylius_classification_variant_value (product_variant_id, classification_id)');

        $this->addSql('ALTER TABLE sylius_classification_translation ADD CONSTRAINT fk_sylius_classification_translation FOREIGN KEY (translatable_id) REFERENCES sylius_classification (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_classification_value ADD CONSTRAINT fk_sylius_classification_value_axis FOREIGN KEY (classification_id) REFERENCES sylius_classification (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_classification_value_translation ADD CONSTRAINT fk_sylius_classification_value_translation FOREIGN KEY (translatable_id) REFERENCES sylius_classification_value (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_classification_variant_value ADD CONSTRAINT fk_sylius_classification_variant_value_variant FOREIGN KEY (product_variant_id) REFERENCES sylius_product_variant (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_classification_variant_value ADD CONSTRAINT fk_sylius_classification_variant_value_value FOREIGN KEY (classification_value_id) REFERENCES sylius_classification_value (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_classification_variant_value ADD CONSTRAINT fk_sylius_classification_variant_value_axis FOREIGN KEY (classification_id) REFERENCES sylius_classification (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    private function createMySqlSchema(): void
    {
        $this->addSql('CREATE TABLE sylius_classification (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(64) NOT NULL, multiple TINYINT(1) NOT NULL, position INT DEFAULT 0 NOT NULL, config JSON DEFAULT NULL, UNIQUE INDEX uniq_sylius_classification_code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_classification_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX idx_sylius_classification_translation (translatable_id), UNIQUE INDEX sylius_classification_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_classification_value (id INT AUTO_INCREMENT NOT NULL, classification_id INT NOT NULL, code VARCHAR(96) NOT NULL, position INT DEFAULT 0 NOT NULL, config JSON DEFAULT NULL, UNIQUE INDEX uniq_sylius_classification_value_code (code), INDEX idx_sylius_classification_value_axis (classification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_classification_value_translation (id INT AUTO_INCREMENT NOT NULL, translatable_id INT NOT NULL, name VARCHAR(255) NOT NULL, locale VARCHAR(255) NOT NULL, INDEX idx_sylius_classification_value_translation (translatable_id), UNIQUE INDEX sylius_classification_value_translation_uniq_trans (translatable_id, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_classification_variant_value (id INT AUTO_INCREMENT NOT NULL, product_variant_id INT NOT NULL, classification_value_id INT NOT NULL, classification_id INT NOT NULL, position INT DEFAULT 0 NOT NULL, UNIQUE INDEX sylius_classification_variant_value_uniq (product_variant_id, classification_value_id), INDEX sylius_classification_variant_value_value_idx (classification_value_id), INDEX sylius_classification_variant_value_axis_idx (product_variant_id, classification_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE sylius_classification_translation ADD CONSTRAINT fk_sylius_classification_translation FOREIGN KEY (translatable_id) REFERENCES sylius_classification (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_classification_value ADD CONSTRAINT fk_sylius_classification_value_axis FOREIGN KEY (classification_id) REFERENCES sylius_classification (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_classification_value_translation ADD CONSTRAINT fk_sylius_classification_value_translation FOREIGN KEY (translatable_id) REFERENCES sylius_classification_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_classification_variant_value ADD CONSTRAINT fk_sylius_classification_variant_value_variant FOREIGN KEY (product_variant_id) REFERENCES sylius_product_variant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sylius_classification_variant_value ADD CONSTRAINT fk_sylius_classification_variant_value_value FOREIGN KEY (classification_value_id) REFERENCES sylius_classification_value (id)');
        $this->addSql('ALTER TABLE sylius_classification_variant_value ADD CONSTRAINT fk_sylius_classification_variant_value_axis FOREIGN KEY (classification_id) REFERENCES sylius_classification (id)');
    }
}
