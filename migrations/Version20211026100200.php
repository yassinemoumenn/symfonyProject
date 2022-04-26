<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211026100200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facturesms (id INT AUTO_INCREMENT NOT NULL, iduser VARCHAR(255) NOT NULL, idboss VARCHAR(255) NOT NULL, nomuser VARCHAR(255) NOT NULL, datecration DATE NOT NULL, nombremessagenv VARCHAR(255) NOT NULL, typemessageenv VARCHAR(255) NOT NULL, taille VARCHAR(255) NOT NULL, unite INT NOT NULL, totale INT NOT NULL, idmessage VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE compagnecsv CHANGE datafilecsv datafilecsv MEDIUMTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE facturesms');
        $this->addSql('ALTER TABLE compagnecsv CHANGE datafilecsv datafilecsv MEDIUMTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
