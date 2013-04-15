<?php
class ModelSaleEasypack24Order extends Model {

    public function updateAfterRegistration($order_id, $parcel_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET easypack24_status = 'Created', easypack24_parcel_id = '" . $this->db->escape($parcel_id) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }

    public function cancelRegistration($order_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET easypack24_status = 'Cancelled', easypack24_parcel_id = '', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }

    public function getEasypack24Order($order_id) {
        $order_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

        if ($order_query->num_rows) {
            return array(
				'email'                   => $order_query->row['email'],
                'telephone'               => $order_query->row['telephone'],
                'machine_id'              => $order_query->row['shipping_address_1'],
                'easypack24_status'       => $order_query->row['easypack24_status'],
                'easypack24_parcel_id'    => $order_query->row['easypack24_parcel_id']
            );
        } else {
            return false;
        }
    }

    public function updateStatus($order_id, $status) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET easypack24_status = '" . $this->db->escape($status) . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }

	public function getOrders($data = array()) {
		$sql = "SELECT o.order_id, CONCAT(o.firstname, ' ', o.lastname) AS customer, (SELECT os.name FROM " . DB_PREFIX . "order_status os WHERE os.order_status_id = o.order_status_id AND os.language_id = '" . (int)$this->config->get('config_language_id') . "') AS status, o.total, o.currency_code, o.currency_value, o.date_added, o.date_modified, o.shipping_address_1, o.easypack24_status, o.easypack24_parcel_id FROM `" . DB_PREFIX . "order` o";

        $sql .= " WHERE o.shipping_code = 'easypack24.easypack24'";

		if (!empty($data['filter_order_status'])) {
			$sql .= " AND o.easypack24_status LIKE '%" . $this->db->escape($data['filter_order_status']) . "%'";
		}

		if (!empty($data['filter_order_id'])) {
			$sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_parcel_id'])) {
			$sql .= " AND o.easypack24_parcel_id LIKE '%" . $this->db->escape($data['filter_parcel_id']) . "%'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		if (!empty($data['filter_date_modified'])) {
			$sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
		}

		if (!empty($data['filter_machine_id'])) {
			$sql .= " AND o.shipping_address_1 LIKE '%" . $data['filter_machine_id'] . "%'";
		}

		$sort_data = array(
			'o.order_id',
			'customer',
			'o.date_added',
			'o.date_modified',
			'o.shipping_address_1',
			'o.easypack24_status',
			'o.easypack24_parcel_id'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY o.order_id";
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

		return $query->rows;
	}

    public function getTotalOrders($data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "order` o";

        $sql .= " WHERE o.shipping_code = 'easypack24.easypack24'";

        if (!empty($data['filter_order_status'])) {
            $sql .= " AND o.easypack24_status LIKE '%" . $this->db->escape($data['filter_order_status']) . "%'";
        }

        if (!empty($data['filter_order_id'])) {
            $sql .= " AND o.order_id = '" . (int)$data['filter_order_id'] . "'";
        }

        if (!empty($data['filter_parcel_id'])) {
            $sql .= " AND o.easypack24_parcel_id LIKE '%" . $this->db->escape($data['filter_parcel_id']) . "%'";
        }

        if (!empty($data['filter_date_added'])) {
            $sql .= " AND DATE(o.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
        }

        if (!empty($data['filter_date_modified'])) {
            $sql .= " AND DATE(o.date_modified) = DATE('" . $this->db->escape($data['filter_date_modified']) . "')";
        }

        if (!empty($data['filter_machine_id'])) {
            $sql .= " AND o.shipping_address_1 LIKE '%" . $data['filter_machine_id'] . "%'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }
}
?>