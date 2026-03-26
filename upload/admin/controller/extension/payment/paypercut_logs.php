<?php

/**
 * Paypercut Logs Viewer
 * Admin page to view API requests, webhook events, and error logs
 */
class ControllerExtensionPaymentPaypercutLogs extends Controller
{

    public function index()
    {
        $this->load->language('extension/payment/paypercut_logs');

        $this->document->setTitle($this->language->get('heading_title'));

        // Check permission - allow access if user has access to sale/order (since this is order-related)
        if (!$this->user->hasPermission('access', 'sale/order')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/paypercut_logs', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Get filter parameters
        $filter_type = isset($this->request->get['filter_type']) ? $this->request->get['filter_type'] : '';
        $filter_date_start = isset($this->request->get['filter_date_start']) ? $this->request->get['filter_date_start'] : date('Y-m-d', strtotime('-7 days'));
        $filter_date_end = isset($this->request->get['filter_date_end']) ? $this->request->get['filter_date_end'] : date('Y-m-d');

        $page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
        $limit = 50;

        $data['filter_type'] = $filter_type;
        $data['filter_date_start'] = $filter_date_start;
        $data['filter_date_end'] = $filter_date_end;

        // Get webhook logs
        $data['webhook_logs'] = $this->getWebhookLogs($filter_type, $filter_date_start, $filter_date_end, $page, $limit);
        $data['webhook_total'] = $this->getWebhookLogsTotal($filter_type, $filter_date_start, $filter_date_end);

        // Get error log
        $data['error_log'] = $this->getErrorLog();

        // Pagination
        $pagination = new Pagination();
        $pagination->total = $data['webhook_total'];
        $pagination->page = $page;
        $pagination->limit = $limit;
        $pagination->url = $this->url->link('extension/payment/paypercut_logs', 'user_token=' . $this->session->data['user_token'] . '&filter_type=' . $filter_type . '&filter_date_start=' . $filter_date_start . '&filter_date_end=' . $filter_date_end . '&page={page}', true);

        $data['pagination'] = $pagination->render();

        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/paypercut_logs', $data));
    }

    /**
     * Get webhook logs from database
     */
    private function getWebhookLogs($filter_type, $date_start, $date_end, $page, $limit)
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "paypercut_webhook_log` WHERE 1=1";

        if ($filter_type) {
            $sql .= " AND event_type = '" . $this->db->escape($filter_type) . "'";
        }

        if ($date_start) {
            $sql .= " AND DATE(created_at) >= '" . $this->db->escape($date_start) . "'";
        }

        if ($date_end) {
            $sql .= " AND DATE(created_at) <= '" . $this->db->escape($date_end) . "'";
        }

        $sql .= " ORDER BY created_at DESC";
        $sql .= " LIMIT " . (($page - 1) * $limit) . ", " . $limit;

        $query = $this->db->query($sql);

        $logs = array();
        foreach ($query->rows as $row) {
            $row['payload_formatted'] = $this->formatJson($row['payload']);
            $logs[] = $row;
        }

        return $logs;
    }

    /**
     * Get total webhook logs count
     */
    private function getWebhookLogsTotal($filter_type, $date_start, $date_end)
    {
        $sql = "SELECT COUNT(*) as total FROM `" . DB_PREFIX . "paypercut_webhook_log` WHERE 1=1";

        if ($filter_type) {
            $sql .= " AND event_type = '" . $this->db->escape($filter_type) . "'";
        }

        if ($date_start) {
            $sql .= " AND DATE(created_at) >= '" . $this->db->escape($date_start) . "'";
        }

        if ($date_end) {
            $sql .= " AND DATE(created_at) <= '" . $this->db->escape($date_end) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    /**
     * Get error log content
     */
    private function getErrorLog()
    {
        $file = DIR_LOGS . 'paypercut_error.log';

        if (file_exists($file)) {
            $size = filesize($file);

            if ($size > 1024 * 1024) { // If larger than 1MB, only show last 100 lines
                $lines = array();
                $handle = fopen($file, 'r');

                if ($handle) {
                    fseek($handle, -min($size, 1024 * 100), SEEK_END); // Go to end - 100KB
                    while (!feof($handle)) {
                        $line = fgets($handle);
                        if ($line !== false) {
                            $lines[] = $line;
                        }
                    }
                    fclose($handle);
                }

                return implode('', array_slice($lines, -100)); // Last 100 lines
            } else {
                return file_get_contents($file);
            }
        }

        return 'No error log found.';
    }

    /**
     * Clear error log
     */
    public function clearErrorLog()
    {
        $json = array();

        if (!$this->user->hasPermission('modify', 'extension/payment/paypercut')) {
            $json['error'] = 'Permission denied';
        } else {
            $file = DIR_LOGS . 'paypercut_error.log';

            if (file_exists($file)) {
                if (@unlink($file)) {
                    $json['success'] = 'Error log cleared successfully';
                } else {
                    $json['error'] = 'Failed to clear error log';
                }
            } else {
                $json['success'] = 'Error log is already empty';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Clear webhook logs
     */
    public function clearWebhookLogs()
    {
        $json = array();

        if (!$this->user->hasPermission('modify', 'extension/payment/paypercut')) {
            $json['error'] = 'Permission denied';
        } else {
            $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "paypercut_webhook_log`");
            $json['success'] = 'Webhook logs cleared successfully';
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Format JSON for display
     */
    private function formatJson($json_string)
    {
        $data = json_decode($json_string, true);
        if ($data) {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        return $json_string;
    }
}
