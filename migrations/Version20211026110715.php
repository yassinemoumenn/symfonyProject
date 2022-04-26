<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211026110715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compagnecsv CHANGE datafilecsv datafilecsv MEDIUMTEXT NOT NULL');
        $this->addSql('ALTER TABLE facturesms CHANGE unite unite VARCHAR(255) NOT NULL, CHANGE totale totale VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compagnecsv CHANGE datafilecsv datafilecsv MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE facturesms CHANGE unite unite INT NOT NULL, CHANGE totale totale INT NOT NULL');
    }
}
