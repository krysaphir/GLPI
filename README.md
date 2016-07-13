# GLPI

Modifications apportées au coeur de GLPI 0.84.6 pour ajouter un SLA de prise en charge.

Ajout colonnes SQL :
ALTER TABLE `glpi_slas` ADD `pec_time` INT(11) NOT NULL AFTER `date_mod`;
ALTER TABLE `glpi_tickets` ADD `due_date_PEC` DATETIME NULL AFTER `locations_id`;
