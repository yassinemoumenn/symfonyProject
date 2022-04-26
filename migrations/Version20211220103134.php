<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211220103134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compagnecsv CHANGE datafilecsv datafilecsv MEDIUMTEXT NOT NULL');
        $this->addSql('ALTER TABLE courrier CHANGE pied pied VARCHAR(255) NOT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE logo logo LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE message ADD sendermessage VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE imageprofil imageprofil MEDIUMTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE compagnecsv CHANGE datafilecsv datafilecsv MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE courrier CHANGE pied pied LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE content content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE logo logo LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE message DROP sendermessage');
        $this->addSql('ALTER TABLE user CHANGE imageprofil imageprofil MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
