<?php
include_once(modification(DIR_APPLICATION . 'model/extension/module/aqe/general.php'));

class ModelExtensionModuleAqeCustomerCustomer extends ModelExtensionModuleAqeGeneral {
	protected static $count = 0;

	public function getCustomers($data = array()) {
		if (isset($data['columns'])) {
			$columns = $data['columns'];
		} else {
			$columns = array('name', 'email', 'customer_group', 'status', 'ip', 'date_added');
		}

		$sql = "SELECT SQL_CALC_FOUND_ROWS c.*, CONCAT(c.firstname, ' ', c.lastname) AS name";

		if (in_array("customer_group", $columns)) {
			$sql .= ", cgd.name AS customer_group";
		}

		if (in_array("company", $columns)) {
			$sql .= ", ca.company";
		}

		if (in_array("website", $columns)) {
			$sql .= ", ca.website";
		}

		if (in_array("tracking", $columns)) {
			$sql .= ", ca.tracking";
		}

		if (in_array("commission", $columns)) {
			$sql .= ", ca.commission";
		}

		if (in_array("tax", $columns)) {
			$sql .= ", ca.tax";
		}

		if (in_array("affiliate_status", $columns)) {
			$sql .= ", ca.status AS affiliate_status";
		}

		$sql .= " FROM `" . DB_PREFIX . "customer` c";

		if (in_array("company", $columns) || in_array("website", $columns) || in_array("tracking", $columns) || in_array("commission", $columns) || in_array("tax", $columns) || in_array("affiliate_status", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "customer_affiliate ca ON (c.customer_id = ca.customer_id)";
		}

		if (in_array("customer_group", $columns)) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "customer_group_description cgd ON (c.customer_group_id = cgd.customer_group_id AND cgd.language_id = '" . (int)$this->config->get('config_language_id') . "')";
		}

		$where = array();

		$int_filters = array(
			'id'                => 'c.customer_id',
			'customer_group'    => 'c.customer_group_id',
			'newsletter'        => 'c.newsletter',
			'safe'              => 'c.safe',
			'status'            => 'c.status',
			'affiliate_status'  => 'ca.status',
		);

		foreach ($int_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				$where[] = "$value = '" . (int)$data["filter_$key"] . "'";
			}
		}

		$float_interval_filters = array(
			'commission'  => 'ca.commission',
		);

		foreach ($float_interval_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_interval_filter')) {
					$where[] = $this->filterInterval($data["filter_$key"], $value);
				} else {
					$where[] = "$value = '" . $this->db->escape($data["filter_$key"]) . "%'";
				}
			}
		}

		$date_filters = array(
			'date_added'        => 'c.date_added',
		);

		foreach ($date_filters as $key => $value) {
			if (isset($data["filter_$key"]) && !is_null($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_interval_filter')) {
					$where[] = $this->filterInterval($this->db->escape($data["filter_$key"]), $value, true);
				} else {
					$where[] = "DATE($value) = DATE('" . $this->db->escape($data["filter_$key"]) . "')";
				}
			}
		}

		$anywhere_filters = array(
			'email'     => 'c.email',
			'telephone' => 'c.telephone',
			'fax'       => 'c.fax',
			'ip'        => 'c.ip',
			'name'      => "CONCAT(c.firstname, ' ', c.lastname)",
			'company'   => 'ca.company',
			'website'   => 'ca.website',
			'tracking'  => 'ca.tracking',
			'tax'       => 'ca.tax',
		);

		foreach ($anywhere_filters as $key => $value) {
			if (!empty($data["filter_$key"])) {
				if ($this->config->get('module_admin_quick_edit_match_anywhere')) {
					$tokens = preg_split("/\s+/", trim($data["filter_$key"]));

					foreach ($tokens as $token) {

						$where[] = "$value LIKE '%" . $this->db->escape($token) . "%'";
					}
				} else {
					$where[] = "$value LIKE '" . $this->db->escape($data["filter_$key"]) . "%'";
				}
			}
		}

		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$sql .= " GROUP BY c.customer_id";

		$sort_data = array(
			'c.customer_id',
			'name',
			'c.email',
			'c.telephone',
			'c.fax',
			'c.newsletter',
			'customer_group',
			'c.status',
			'c.safe',
			'c.ip',
			'c.date_added',
			'ca.company',
			'ca.website',
			'ca.tracking',
			'ca.tax',
			'ca.commission',
			'ca.status',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		$count = $this->db->query("SELECT FOUND_ROWS() AS count");
		$this->count = ($count->num_rows) ? (int)$count->row['count'] : 0;

		return $query->rows;
	}

	public function getTotalCustomers() {
		return $this->count;
	}

	public function quickEditCustomer($customer_id, $column, $value, $lang_id=null, $data=null) {
		$editable = array('name', 'email', 'telephone', 'newsletter', 'customer_group', 'safe', 'status', 'company', 'website', 'tracking', 'tax', 'commission', 'affiliate_status');
		$result = false;
		if (in_array($column, $editable)) {
			if (in_array($column, array('email', 'telephone', 'fax')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET " . $column . " = '" . $this->db->escape($value) . "' WHERE customer_id = '" . (int)$customer_id . "'");
			else if (in_array($column, array('company', 'website', 'tracking', 'tax', 'commission')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "customer_affiliate` SET " . $column . " = '" . $this->db->escape($value) . "' WHERE customer_id = '" . (int)$customer_id . "'");
			else if (in_array($column, array('affiliate_status')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "customer_affiliate` SET status = '" . (int)$value . "' WHERE customer_id = '" . (int)$customer_id . "'");
			else if (in_array($column, array('newsletter', 'safe', 'status')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET " . $column . " = '" . (int)$value . "' WHERE customer_id = '" . (int)$customer_id . "'");
			else if (in_array($column, array('customer_group')))
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET " . $column . "_id = '" . (int)$value . "' WHERE customer_id = '" . (int)$customer_id . "'");
			else if ($column == "name") {
				$first_name = $data['first_name'];
				$last_name = $data['last_name'];
				$result = $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET firstname = '" . $this->db->escape($first_name) . "', lastname = '" . $this->db->escape($last_name) . "' WHERE customer_id = '" . (int)$customer_id . "'");
			}
		}

		return $result;
	}
}
