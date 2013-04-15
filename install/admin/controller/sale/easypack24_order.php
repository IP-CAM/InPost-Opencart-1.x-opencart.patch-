<?php
class ControllerSaleEasypack24Order extends Controller
{
    private $error = array();

    public function index()
    {
        $this->language->load('sale/easypack24_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/easypack24_order');

        $this->getList();
    }

    public function cancel()
    {
        $this->language->load('sale/easypack24_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/easypack24_order');

        $order_id = $this->request->get['order_id'];
        $order = $this->model_sale_easypack24_order->getEasypack24Order($order_id);

        $response = $this->easypack24_helper->connectEasypack24(
            array(
                'url' => $this->config->get('easypack24_api_url') . 'parcels',
                'methodType' => 'PUT',
                'params' => array(
                    'receiver' => array(
                        'id' => $order['easypack24_parcel_id'],
                        'email' => $order['email']
                    )
                )
            )
        );

        if ($response['info']['http_code'] != '204') {
            $this->model_sale_easypack24_order->cancelRegistration($order_id);
        } else {
            $message = (array)$response['result'];
            $this->error['warning'] = $this->language->get('error_cancel_registration') . reset($message);
        }

        $this->getList();
    }

    public function refresh()
    {
        $this->language->load('sale/easypack24_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/easypack24_order');

        $order_id = $this->request->get['order_id'];
        $order = $this->model_sale_easypack24_order->getEasypack24Order($order_id);

        $response = $this->easypack24_helper->connectEasypack24(
            array(
                'url' => $this->config->get('easypack24_api_url') . 'parcels/' . $order['easypack24_parcel_id'],
                'methodType' => 'GET',
                'params' => array()
            )
        );

        if ($response['info']['http_code'] == '200') {
            $this->model_sale_easypack24_order->updateStatus($order_id, $response['result']->status);
        } else {
            $message = (array)$response['result'];
            $this->error['warning'] = $this->language->get('error_refresh_registration') . reset($message);
        }

        $this->getList();
    }

    public function sticker()
    {
        $this->language->load('sale/easypack24_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/easypack24_order');

        $order_id = $this->request->get['order_id'];
        $order = $this->model_sale_easypack24_order->getEasypack24Order($order_id);

        if ($order['easypack24_status'] == 'Created') {
            $response = $this->easypack24_helper->connectEasypack24(
                array(
                    'url' => $this->config->get('easypack24_api_url') . 'parcels/' . $order['easypack24_parcel_id'] . '/pay',
                    'methodType' => 'POST',
                    'params' => array()
                )
            );
            if ($response['info']['http_code'] == '204') {
                $this->model_sale_easypack24_order->updateStatus($order_id, 'Prepared');
            } else {
                $message = (array)$response['result'];
                $this->error['warning'] = $this->language->get('error_pay_for_parcel') . reset($message);
            }
        }

        if (empty($this->error['warning'])) {
            $response = $this->easypack24_helper->connectEasypack24(
                array(
                    'url' => $this->config->get('easypack24_api_url') . 'stickers/' . $order['easypack24_parcel_id'],
                    'methodType' => 'GET',
                    'params' => array(
                        'format' => 'Pdf',
                        'type' => 'normal'
                    )
                )
            );

            if ($response['info']['http_code'] == '200') {
                $pdf = base64_decode($response['result']);

                header('Content-type: application/pdf');
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename=order-' . $order['easypack24_parcel_id'] . '.pdf');
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . strlen($pdf));

                echo $pdf;
            } else {
                $message = (array)$response['result'];
                $this->error['warning'] = $this->language->get('error_sticker') . reset($message);
            }
        }

        if (!empty($this->error['warning'])) {
            $this->getList();
        }
    }

    public function register()
    {
        $this->language->load('sale/easypack24_order');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('sale/easypack24_order');

        $order_id = $this->request->get['order_id'];
        $order = $this->model_sale_easypack24_order->getEasypack24Order($order_id);

        if (!empty($order['easypack24_parcel_id'])) {
            $this->error['warning'] = $this->language->get('error_already_registered');
        } else {
            $response = $this->easypack24_helper->connectEasypack24(
                array(
                    'url' => $this->config->get('easypack24_api_url') . 'parcels',
                    'methodType' => 'POST',
                    'params' => array(
                        'receiver' => array(
                            'phone' => $order['telephone'],
                            'email' => $order['email']
                        ),
                        'size' => 'A',
                        'tmp_id' => '1',
                        'target_machine' => $order['machine_id']
                    )
                )
            );
            if (!empty($response['info']['redirect_url'])) {
                $url_array = explode('/', $response['info']['redirect_url']);
                $parcel_array = explode('?', end($url_array));
                $parcel_id = reset($parcel_array);
                $this->model_sale_easypack24_order->updateAfterRegistration($order_id, $parcel_id);
            } else {
                $message = (array)$response['result'];
                $this->error['warning'] = $this->language->get('error_registration') . reset($message);
            }
        }

        $this->getList();
    }

    protected function getList()
    {
        if (isset($this->request->get['filter_order_id'])) {
            $filter_order_id = $this->request->get['filter_order_id'];
        } else {
            $filter_order_id = null;
        }

        if (isset($this->request->get['filter_parcel_id'])) {
            $filter_parcel_id = $this->request->get['filter_parcel_id'];
        } else {
            $filter_parcel_id = null;
        }

        if (isset($this->request->get['filter_order_status'])) {
            $filter_order_status = $this->request->get['filter_order_status'];
        } else {
            $filter_order_status = null;
        }

        if (isset($this->request->get['filter_machine_id'])) {
            $filter_machine_id = $this->request->get['filter_machine_id'];
        } else {
            $filter_machine_id = null;
        }

        if (isset($this->request->get['filter_date_added'])) {
            $filter_date_added = $this->request->get['filter_date_added'];
        } else {
            $filter_date_added = null;
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $filter_date_modified = $this->request->get['filter_date_modified'];
        } else {
            $filter_date_modified = null;
        }

        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'o.order_id';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'DESC';
        }

        if (isset($this->request->get['page'])) {
            $page = $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_parcel_id'])) {
            $url .= '&filter_parcel_id=' . urlencode(html_entity_decode($this->request->get['filter_parcel_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_order_status'])) {
            $url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
        }

        if (isset($this->request->get['filter_machine_id'])) {
            $url .= '&filter_machine_id=' . $this->request->get['filter_machine_id'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('sale/easypack24_order', 'token=' . $this->session->data['token'] . $url, 'SSL'),
            'separator' => ' :: '
        );

        $this->data['sticker'] = $this->url->link('sale/easypack24_order/sticker', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['refresh'] = $this->url->link('sale/easypack24_order/refresh', 'token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('sale/easypack24_order/cancel', 'token=' . $this->session->data['token'] . $url, 'SSL');

        $this->data['orders'] = array();

        $data = array(
            'filter_order_id' => $filter_order_id,
            'filter_parcel_id' => $filter_parcel_id,
            'filter_order_status' => $filter_order_status,
            'filter_machine_id' => $filter_machine_id,
            'filter_date_added' => $filter_date_added,
            'filter_date_modified' => $filter_date_modified,
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_admin_limit'),
            'limit' => $this->config->get('config_admin_limit')
        );

        $order_total = $this->model_sale_easypack24_order->getTotalOrders($data);

        $results = $this->model_sale_easypack24_order->getOrders($data);

        foreach ($results as $result) {
            $action = array();

            if (empty($result['easypack24_parcel_id'])) {
                $action[] = array(
                    'text' => $this->language->get('text_register'),
                    'href' => $this->url->link('sale/easypack24_order/register', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL')
                );
            } else if (!empty($result['easypack24_status'])) {
                $action[] = array(
                    'text' => $this->language->get('text_sticker'),
                    'href' => $this->url->link('sale/easypack24_order/sticker', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL'),
                    'target' => '_blank'
                );
                if ($result['easypack24_status'] == 'Created') {
                    $action[] = array(
                        'text' => $this->language->get('text_cancel'),
                        'href' => $this->url->link('sale/easypack24_order/cancel', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL')
                    );
                }
                $action[] = array(
                    'text' => $this->language->get('text_refresh'),
                    'href' => $this->url->link('sale/easypack24_order/refresh', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL')
                );
            }

            $action[] = array(
                'text' => $this->language->get('text_view'),
                'href' => $this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL')
            );

            if (strtotime($result['date_added']) > strtotime('-' . (int)$this->config->get('config_order_edit') . ' day')) {
                $action[] = array(
                    'text' => $this->language->get('text_edit'),
                    'href' => $this->url->link('sale/order/update', 'token=' . $this->session->data['token'] . '&order_id=' . $result['order_id'] . $url, 'SSL')
                );
            }

            $this->data['orders'][] = array(
                'order_id' => $result['order_id'],
                'machine_id' => $result['shipping_address_1'],
                'status' => $result['easypack24_status'],
                'parcel_id' => $result['easypack24_parcel_id'],
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
                'action' => $action
            );
        }

        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['text_no_results'] = $this->language->get('text_no_results');
        $this->data['text_missing'] = $this->language->get('text_missing');

        $this->data['column_order_id'] = $this->language->get('column_order_id');
        $this->data['column_parcel_id'] = $this->language->get('column_parcel_id');
        $this->data['column_status'] = $this->language->get('column_status');
        $this->data['column_machine_id'] = $this->language->get('column_machine_id');
        $this->data['column_date_added'] = $this->language->get('column_date_added');
        $this->data['column_date_modified'] = $this->language->get('column_date_modified');
        $this->data['column_action'] = $this->language->get('column_action');

        $this->data['button_sticker'] = $this->language->get('button_sticker');
        $this->data['button_refresh'] = $this->language->get('button_refresh');
        $this->data['button_cancel'] = $this->language->get('button_cancel');
        $this->data['button_filter'] = $this->language->get('button_filter');

        $this->data['token'] = $this->session->data['token'];

        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $this->data['success'] = '';
        }

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_parcel_id'])) {
            $url .= '&filter_parcel_id=' . urlencode(html_entity_decode($this->request->get['filter_parcel_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_order_status'])) {
            $url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
        }

        if (isset($this->request->get['filter_machine_id'])) {
            $url .= '&filter_machine_id=' . $this->request->get['filter_machine_id'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $this->data['sort_order'] = $this->url->link('sale/easypack24_order', 'token=' . $this->session->data['token'] . '&sort=o.order_id' . $url, 'SSL');
        $this->data['sort_parcel_id'] = $this->url->link('sale/easypack24_order', 'token=' . $this->session->data['token'] . '&sort=o.easypack24_parcel_id' . $url, 'SSL');
        $this->data['sort_status'] = $this->url->link('sale/easypack24_order', 'token=' . $this->session->data['token'] . '&sort=o.easypack24_status' . $url, 'SSL');
        $this->data['sort_total'] = $this->url->link('sale/easypack24_order', 'token=' . $this->session->data['token'] . '&sort=o.shipping_address_1' . $url, 'SSL');
        $this->data['sort_date_added'] = $this->url->link('sale/easypack24_order', 'token=' . $this->session->data['token'] . '&sort=o.date_added' . $url, 'SSL');
        $this->data['sort_date_modified'] = $this->url->link('sale/easypack24_order', 'token=' . $this->session->data['token'] . '&sort=o.date_modified' . $url, 'SSL');

        $url = '';

        if (isset($this->request->get['filter_order_id'])) {
            $url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
        }

        if (isset($this->request->get['filter_parcel_id'])) {
            $url .= '&filter_parcel_id=' . urlencode(html_entity_decode($this->request->get['filter_parcel_id'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($this->request->get['filter_order_status'])) {
            $url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
        }

        if (isset($this->request->get['filter_machine_id'])) {
            $url .= '&filter_machine_id=' . $this->request->get['filter_machine_id'];
        }

        if (isset($this->request->get['filter_date_added'])) {
            $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
        }

        if (isset($this->request->get['filter_date_modified'])) {
            $url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
        }

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $pagination = new Pagination();
        $pagination->total = $order_total;
        $pagination->page = $page;
        $pagination->limit = $this->config->get('config_admin_limit');
        $pagination->text = $this->language->get('text_pagination');
        $pagination->url = $this->url->link('sale/order', 'token=' . $this->session->data['token'] . $url . '&page={page}', 'SSL');

        $this->data['pagination'] = $pagination->render();

        $this->data['filter_order_id'] = $filter_order_id;
        $this->data['filter_parcel_id'] = $filter_parcel_id;
        $this->data['filter_order_status'] = $filter_order_status;
        $this->data['filter_machine_id'] = $filter_machine_id;
        $this->data['filter_date_added'] = $filter_date_added;
        $this->data['filter_date_modified'] = $filter_date_modified;

        $this->load->model('localisation/order_status');

        $this->data['sort'] = $sort;
        $this->data['order'] = $order;

        $this->template = 'sale/easypack24_order_list.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );

        $this->response->setOutput($this->render());
    }
}
?>
