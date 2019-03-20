<?php
/**
 * @package		LogshubSearch
 * @author		Golden Development Ltd.
 * @copyright	Copyright (c) 2019, Golden Development Ltd. (https://www.logshub.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.logshub.com
*/

include_once DIR_SYSTEM . '/library/logshub-search-client/all.php';

class ControllerExtensionModuleLogshubSearch extends Controller
{
	const CONFIG_KEYS = ['enabled','service_id','api_url','api_hash','api_secret','public_key'];

	private $error = [];

	/**
	 * Main action - configuration page (GET + POST)
	 */
	public function index()
    {
		$this->load->language('extension/module/logshub_search');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

        if (!$this->hasPermission()) {
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));

            return;
        }

		if ($this->isPost()) {
			$this->model_setting_setting->editSetting('module_logshub_search', $this->getSettingsArrayFromPost());
			$this->session->data['success'] = $this->language->get('save_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));

            return;
		}
        $data = $this->getVarArray();
        $data = array_merge($data, $this->getSettingsArrayFromConfig());

		$this->response->setOutput($this->load->view('extension/module/logshub_search', $data));
	}

	/**
	 * Cron action
	 */
    public function cron()
    {
		if (!$this->config->get('module_logshub_search_enabled')) {
			$this->log('INFO: synchronization disabled');
			
			return;
		}
        $this->load->model('logshub/indexer');
		$serviceId = $this->config->get('module_logshub_search_service_id');

		try {
			$client = $this->model_logshub_indexer->getClient(
				$this->config->get('module_logshub_search_api_url'),
				$this->config->get('module_logshub_search_api_hash'),
				$this->config->get('module_logshub_search_api_secret')
			);
			$products = $this->model_logshub_indexer->getApiProducts();
			$this->model_logshub_indexer->indexProducts($client, $serviceId, $products);

			$categories = $this->model_logshub_indexer->getApiCategories();
			$this->model_logshub_indexer->indexCategories($client, $serviceId, $categories);

		} catch (\Exception $e) {
			$msg = 'ERROR: ' . $e->getMessage();
			if ($e->getPrevious()) {
				$msg .= '; ' . $e->getPrevious()->getMessage();
			}
			$this->log($msg);
		}
    }

    /**
     * @see ModelSettingCron
     */
    public function install() {
		$this->load->model('setting/cron');
        $this->model_setting_cron->addCron('logshubsearch', 'day', 'extension/module/logshub_search/cron', 1);
	}

	public function uninstall() {
		$this->load->model('setting/cron');
		$this->model_setting_cron->deleteCronByCode('logshubsearch');
	}

    private function getSettingsArrayFromPost()
    {
        $settings = [];
        foreach (self::CONFIG_KEYS as $key) {
            if (!empty($this->request->post['module_logshub_search_'.$key])){
                $settings['module_logshub_search_'.$key] = $this->request->post['module_logshub_search_'.$key];
            }
        }

        return $settings;
    }

    private function getSettingsArrayFromConfig()
    {
        $settings = [];
        foreach (self::CONFIG_KEYS as $key) {
            if (!empty($this->config->get('module_logshub_search_'.$key))){
                $settings['module_logshub_search_'.$key] = $this->config->get('module_logshub_search_'.$key);
            }
        }

        return $settings;
    }

    private function isPost()
    {
        return $this->request->server['REQUEST_METHOD'] == 'POST';
    }

	private function hasPermission()
    {
		if (!$this->user->hasPermission('modify', 'extension/module/logshub_search')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

    private function getVarArray()
    {
        $this->load->model('localisation/language');

        return [
            'breadcrumbs' => [
                [
        			'text' => $this->language->get('text_home'),
        			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        		],
                [
        			'text' => $this->language->get('text_extension'),
        			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        		],
                [
        			'text' => $this->language->get('heading_title'),
        			'href' => $this->url->link('extension/module/logshub_search', 'user_token=' . $this->session->data['user_token'], true)
        		]
            ],
            'action' => $this->url->link('extension/module/logshub_search', 'user_token=' . $this->session->data['user_token'], true),
		    'cancel' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
            'languages' => $this->model_localisation_language->getLanguages(['sort' => 'code']),
		    'header' => $this->load->controller('common/header'),
		    'column_left' => $this->load->controller('common/column_left'),
		    'footer' => $this->load->controller('common/footer'),
            'current_lang_id' => $this->config->get('config_language_id'),
        ];
    }

	private function log($message)
	{
		$log = new Log('logshubsearch.log');
		$log->write($message);
	}
}
