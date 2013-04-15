<?php
class ControllerShippingEasypack24 extends Controller
{
    private $error = array();

    public function index()
    {
        $this->language->load('shipping/easypack24');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('easypack24', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->redirect($this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $this->setDataFromLanguage('heading_title');
        $this->setDataFromLanguage('text_enabled');
        $this->setDataFromLanguage('text_disabled');
        $this->setDataFromLanguage('text_all_zones');
        $this->setDataFromLanguage('text_none');

        $this->setDataFromLanguage('entry_api_url');
        $this->setDataFromLanguage('entry_api_key');
        $this->setDataFromLanguage('entry_price');
        $this->setDataFromLanguage('entry_max_weight');
        $this->setDataFromLanguage('entry_max_dimension_a');
        $this->setDataFromLanguage('entry_max_dimension_b');
        $this->setDataFromLanguage('entry_max_dimension_c');
        $this->setDataFromLanguage('entry_geo_zone');
        $this->setDataFromLanguage('entry_tax_class');
        $this->setDataFromLanguage('entry_status');
        $this->setDataFromLanguage('entry_sort_order');

        $this->setDataFromLanguage('button_save');
        $this->setDataFromLanguage('button_cancel');

        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        $this->setBreadcrumbs();

        $this->data['action'] = $this->url->link('shipping/easypack24', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL');

        $this->load->model('localisation/geo_zone');
        $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->load->model('localisation/tax_class');
        $this->data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        $this->setDataFromParameter('easypack24_api_url');
        $this->setDataFromParameter('easypack24_api_key');
        $this->setDataFromParameter('easypack24_price');
        $this->setDataFromParameter('easypack24_max_weight');
        $this->setDataFromParameter('easypack24_max_dimension_a');
        $this->setDataFromParameter('easypack24_max_dimension_b');
        $this->setDataFromParameter('easypack24_max_dimension_c');
        $this->setDataFromParameter('easypack24_geo_zone_id');
        $this->setDataFromParameter('easypack24_tax_class_id');
        $this->setDataFromParameter('easypack24_status');
        $this->setDataFromParameter('easypack24_sort_order');

        $this->template = 'shipping/easypack24.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render());
    }

    private function setDataFromLanguage($name)
    {
        $this->data[$name] = $this->language->get($name);
    }

    private function setBreadcrumbs()
    {
        $this->data['breadcrumbs'] = array();
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('shipping/easypack24', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
    }

    private function setDataFromParameter($name)
    {
        if (isset($this->request->post[$name])) {
            $this->data[$name] = $this->request->post[$name];
        } else {
            $this->data[$name] = $this->config->get($name);
        }
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'shipping/easypack24')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}

?>