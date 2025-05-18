<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250517083116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE orders CHANGE cur_rate cur_rate DOUBLE PRECISION DEFAULT 1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE orders_article CHANGE cur_rate cur_rate DOUBLE PRECISION DEFAULT 1
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE orders CHANGE cur_rate cur_rate DOUBLE PRECISION DEFAULT '1'
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE orders_article CHANGE cur_rate cur_rate DOUBLE PRECISION DEFAULT '1'
        SQL);
    }
}
