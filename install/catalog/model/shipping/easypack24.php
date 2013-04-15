<?php
class ModelShippingEasypack24 extends Model
{
    function getQuote($address)
    {
        $this->language->load('shipping/easypack24');

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('easypack24_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if (!$this->config->get('easypack24_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        // check size - check weight code as well, it can be in LBS, not KG
        $weight = $this->cart->getWeight();
        $max_weight = $this->config->get('easypack24_max_weight');

        $error = false;
        if (!$weight || $weight <= 0 || $weight > $max_weight) {
            $error = $this->language->get('error_max_weight') . $max_weight;
        }

        $dimension = 0;
        foreach ($this->cart->getProducts() as $product) {
            if ($product['shipping']) {
                $length = $this->length->convert($product['length'], $product['length_class_id'], $this->config->get('config_length_class_id'));
                $width = $this->width;
                $height = $this->height;
                $dimension = $length + $width + $height;
            }
        }

        $max_dimension_c = $this->config->get('easypack24_max_dimension_c');

        $max_dimension_array = explode('x', strtolower(trim($max_dimension_c)));
        $max_length = (float)trim($max_dimension_array[0]);
        $max_width = (float)trim($max_dimension_array[1]);
        $max_height = (float)trim($max_dimension_array[2]);
        $max_dimension = $max_length + $max_width + $max_height;

        if ($dimension > $max_dimension) {
            $error = $this->language->get('error_max_dimension') . $max_dimension_c;
        }


        $method_data = array();

        if ($status) {
            $quote_data = array();

            $quote_data['easypack24'] = array(
                'code' => 'easypack24.easypack24',
                'title' => $this->language->get('text_description'),
                'cost' => $this->config->get('easypack24_price'),
                'text' => $this->currency->format($this->config->get('easypack24_price'))
            );

            $method_data = array(
                'code' => 'easypack24',
                'title' => $this->language->get('text_title'),
                'quote' => $quote_data,
                'sort_order' => $this->config->get('easypack24_sort_order'),
                'error' => $error
            );
        }

        return $method_data;
    }
}

?>