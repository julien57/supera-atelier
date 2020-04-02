<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200327224846 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE reservation_workshop_date');
        $this->addSql('ALTER TABLE reservation ADD workshop_date_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955FAD8247C FOREIGN KEY (workshop_date_id) REFERENCES workshop_date (id)');
        $this->addSql('CREATE INDEX IDX_42C84955FAD8247C ON reservation (workshop_date_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reservation_workshop_date (reservation_id INT NOT NULL, workshop_date_id INT NOT NULL, INDEX IDX_7E756E60B83297E7 (reservation_id), INDEX IDX_7E756E60FAD8247C (workshop_date_id), PRIMARY KEY(reservation_id, workshop_date_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE reservation_workshop_date ADD CONSTRAINT FK_7E756E60B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_workshop_date ADD CONSTRAINT FK_7E756E60FAD8247C FOREIGN KEY (workshop_date_id) REFERENCES workshop_date (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955FAD8247C');
        $this->addSql('DROP INDEX IDX_42C84955FAD8247C ON reservation');
        $this->addSql('ALTER TABLE reservation DROP workshop_date_id');
    }
}
