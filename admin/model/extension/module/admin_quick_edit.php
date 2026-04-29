<?php
class ModelExtensionModuleAdminQuickEdit extends Model {
	// Events
	public function getEventByCodeTriggerAction($code, $trigger, $action) {
		$event = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->db->escape($code) . "' AND `trigger` = '" . $this->db->escape($trigger) . "' AND `action` = '" . $this->db->escape($action) . "'");

		return $event->rows;
	}
}
