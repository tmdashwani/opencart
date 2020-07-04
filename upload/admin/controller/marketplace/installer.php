<?php
class ControllerMarketplaceInstaller extends Controller {
	public function index() {
		$this->load->language('marketplace/installer');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketplace/installer', 'user_token=' . $this->session->data['user_token'])
		);

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['filter_extension_download_id'])) {
			$data['filter_extension_download_id'] = $this->request->get['filter_extension_download_id'];
		} else {
			$data['filter_extension_download_id'] = '';
		}

		/*
		// Code to grab pre installed extensions
		$extensions = $this->model_setting_extension->getDownloaded('analytics');

		$curl = curl_init(OPENCART_SERVER . 'index.php?route=api/core&version=' . VERSION);

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($curl, CURLOPT_POST, 1);

		$response = curl_exec($curl);

		curl_close($curl);

		$response_info = json_decode($response, true);

		foreach ($response_info['extension'] as $extension) {
			$this->model_setting_extension->addExtension($extension, '');
		}

		echo VERSION . "\n";
		echo $response;
		*/

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('marketplace/installer', $data));
	}

	public function extension() {
		$this->load->language('marketplace/installer');

		if (isset($this->request->get['filter_extension_download_id'])) {
			$filter_extension_download_id = $this->request->get['filter_extension_download_id'];
		} else {
			$filter_extension_download_id = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['extensions'] = array();
		
		$this->load->model('setting/extension');

		$filter_data = array(
			'filter_extension_download_id' => $filter_extension_download_id,
			'sort'                         => $sort,
			'order'                        => $order,
			'start'                        => ($page - 1) * $this->config->get('config_pagination'),
			'limit'                        => $this->config->get('config_pagination')
		);

		$extension_total = $this->model_setting_extension->getTotalInstalls($filter_data);

		$results = $this->model_setting_extension->getInstalls($filter_data);
		
		foreach ($results as $result) {
			$data['extensions'][] = array(
				'name'       => $result['name'],
				'version'    => $result['version'],
				'image'      => $result['image'],
				'author'     => $result['author'],
				'status'     => $result['status'],
				'link'       => $this->url->link('marketplace/marketplace/info', 'user_token=' . $this->session->data['user_token'] . '&extension_id=' . $result['extension_id']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'install'    => $this->url->link('marketplace/installer/install', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $result['extension_install_id']),
				'uninstall'  => $this->url->link('marketplace/installer/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $result['extension_install_id']),
				'delete'     => $this->url->link('marketplace/installer/delete', 'user_token=' . $this->session->data['user_token'] . '&extension_install_id=' . $result['extension_install_id'])
			);
		}

		$data['results'] = sprintf($this->language->get('text_pagination'), ($extension_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($extension_total - 10)) ? $extension_total : ((($page - 1) * 10) + 10), $extension_total, ceil($extension_total / 10));

		$url = '';

		if (isset($this->request->get['filter_extension_id'])) {
			$url .= '&filter_extension_id=' . $this->request->get['filter_extension_id'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$data['sort_name'] = $this->url->link('marketplace/installer/extension', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url);
		$data['sort_version'] = $this->url->link('marketplace/installer/extension', 'user_token=' . $this->session->data['user_token'] . '&sort=version' . $url);
		$data['sort_date_added'] = $this->url->link('marketplace/installer/extension', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_date_added' . $url);

		$data['pagination'] = $this->load->controller('common/pagination', array(
			'total' => $extension_total,
			'page'  => $page,
			'limit' => 10,
			'url'   => $this->url->link('marketplace/installer/extension', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$this->response->setOutput($this->load->view('marketplace/installer_extension', $data));
	}

	public function upload() {
		$this->load->language('marketplace/installer');

		$json = array();

		// Check for any install directories
		if (isset($this->request->files['file']['name'])) {


			$filename = $this->request->files['file']['name'];

			if (substr($filename, -10) != '.ocmod.zip') {
				$json['error'] = $this->language->get('error_filetype');
			}

			if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
				$json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
			}

		} else {
			$json['error'] = $this->language->get('error_upload');
		}






		if (!$json) {
			$file = DIR_STORAGE . 'marketplace/' . basename($filename, '.ocmod.zip');

			move_uploaded_file($this->request->files['file']['tmp_name'], $file);

			if (is_file($file)) {
				// Unzip the files
				$zip = new ZipArchive();

				if ($zip->open($file)) {
					$xml = $zip->getFromName('install.xml');

					$zip->close();
				}

				// If xml file just put it straight into the DB
				if ($xml) {
					try {
						$dom = new DOMDocument('1.0', 'UTF-8');
						$dom->loadXml($xml);

						$name = $dom->getElementsByTagName('name')->item(0);

						if ($name) {
							$name = $name->nodeValue;
						} else {
							$name = '';
						}

						$version = $dom->getElementsByTagName('version')->item(0);

						if ($version) {
							$version = $version->nodeValue;
						} else {
							$version = '';
						}

						$author = $dom->getElementsByTagName('author')->item(0);

						if ($author) {
							$author = $author->nodeValue;
						} else {
							$author = '';
						}

						$link = $dom->getElementsByTagName('link')->item(0);

						if ($link) {
							$link = $link->nodeValue;
						} else {
							$link = '';
						}
					} catch(Exception $exception) {
						$json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
					}

					if (!$json) {
						$extension_data = array(
							'name'     => $name,
							'author'   => $author,
							'version'  => $version,
							'filename' => $filename,
							'link'     => $link,
							'status'   => 0
						);

						$this->load->model('setting/extension');

						$this->model_setting_extension->addInstall($extension_data);
					}
				}

				$json['success'] = $this->language->get('text_success');
			} else {
				$json['error'] = $this->language->get('error_file');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function install() {
		$this->load->language('marketplace/installer');

		$json = array();

		if (isset($this->request->get['extension_install_id'])) {
			$extension_install_id = $this->request->get['extension_install_id'];
		} else {
			$extension_install_id = 0;
		}

		if (!$this->user->hasPermission('modify', 'marketplace/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$this->load->model('setting/extension');

		$extension_install_info = $this->model_setting_extension->getInstall($extension_install_id);

		if ($extension_install_info) {
			$file = DIR_STORAGE . 'marketplace/' . $extension_install_info['filename'];

			if (!is_file($file)) {
				$json['error'] = sprintf($this->language->get('error_file_missing'), $extension_install_info['filename']);
			}

			$directory = basename($extension_install_info['filename'], '.ocmod.zip') . '/';

			if (is_dir(DIR_EXTENSION . $directory)) {
				//$json['error'] = sprintf($this->language->get('error_directory_exists'), $directory);
			}
		} else {
			$json['error'] = $this->language->get('error_install');
		}

		$extract = array();

		if (!$json) {
			// Unzip the files
			$zip = new ZipArchive();

			if ($zip->open($file)) {
				// Check if any of the files already exist.
				for ($i = 0; $i < $zip->numFiles; $i++) {
					$source = $zip->getNameIndex($i);

					// Only extract the contents of the upload folder
					$destination = str_replace('\\', '/', substr($source, strlen('upload/')));

					$path = '';
					$base = '';

					// admin > extension/{directory}/admin
					if (substr($destination, 0, 6) == 'admin/') {
						$path = $directory . $destination;
						$base = DIR_EXTENSION;
					}

					// catalog > extension/{directory}/catalog
					if (substr($destination, 0, 8) == 'catalog/') {
						$path = $directory . $destination;
						$base = DIR_EXTENSION;
					}

					// image > image
					if (substr($destination, 0, 6) == 'image/') {
						$path = substr($destination, 6);
						$base = DIR_IMAGE;
					}

					// system/config > system/config
					if (substr($destination, 0, 14) == 'system/config/') {
						$path = substr($destination, 14);
						$base = DIR_CONFIG;
					}

					// system/helper > extension/{directory}/system/helper
					if (substr($destination, 0, 14) == 'system/helper/') {
						$path = $directory . $destination;
						$base = DIR_EXTENSION;
					}

					// system/library > extension/{directory}/system/library
					if (substr($destination, 0, 15) == 'system/library/') {
						$path = $directory . $destination;
						$base = DIR_EXTENSION;
					}

					// Must be substr
					// system/storage/vendor > system/storage/vendor
					if (substr($destination, 0, 22) == 'system/storage/vendor/') {
						$path = substr($destination, 22);
						$base = DIR_STORAGE . 'vendor/';
					}

					if ($path) {
						if (!is_file($base . $path)) {
							$extract[] = array(
								'source'      => $source,
								'destination' => $destination,
								'base'        => $base,
								'path'        => $path
							);
						} else {
							$json['error'] = sprintf($this->language->get('error_exists') . ' %s', $source);

							break;
						}
					}
				}

				$zip->close();
			} else {
				$json['error'] = $this->language->get('error_unzip');
			}
		}

		if (!$json) {
			foreach ($extract as $copy) {
				echo "\n" . '-------------------------------' . "\n";
				echo 'source: '  . $copy['source'] . "\n";
				echo 'destination: '  . $copy['destination'] . "\n";
				echo 'base: '  . $copy['base'] . "\n";
				echo 'path: '  . $copy['path'] . "\n";

				// Must have a path before directories before files can be moved
				if (substr($copy['path'], -1) == '/' && !is_dir($copy['base'] . $copy['path'])) {
					$string = '';

					// If no size then we assume entry is a directory
					$parts = explode('/', trim($copy['path'], '/'));

					foreach ($parts as $part) {
						$string .= $part . '/';

						if (!is_dir($copy['base'] . $string)) {
							if (mkdir($copy['base'] . $string, 0777)) {
								$this->model_setting_extension->addPath($extension_install_id, $copy['destination']);

								echo 'dir added: ' . $copy['base'] . $string . "\n";
							}
						}
					}
				}

				// If check if the path is not directory and check there is no existing file
				if (substr($copy['path'], -1) != '/') {
					if (copy('zip://' . $file . '#' . $copy['source'], $copy['base'] . $copy['path'])) {
						$this->model_setting_extension->addPath($extension_install_id, $copy['destination']);

						echo 'file added: ' . $copy['base'] . $copy['path'] . "\n";
					}
				}
			}

			$this->model_setting_extension->editStatus($extension_install_id, true);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function uninstall() {
		$this->load->language('marketplace/installer');

		$json = array();

		if (isset($this->request->get['extension_install_id'])) {
			$extension_install_id = $this->request->get['extension_install_id'];
		} else {
			$extension_install_id = 0;
		}

		if (!$this->user->hasPermission('modify', 'marketplace/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$this->load->model('setting/extension');

		$extension_install_info = $this->model_setting_extension->getInstall($extension_install_id);

		if ($extension_install_info) {
			$directory =  basename($extension_install_info['filename'], '.ocmod.zip') . '/';

			if (!is_dir(DIR_EXTENSION . $directory)) {
				$json['error'] = $this->language->get('error_directory_missing');
			}
		} else {
			$json['error'] = $this->language->get('error_install');
		}

		if (!$json) {
			$results = $this->model_setting_extension->getPathsByExtensionInstallId($extension_install_id);

			rsort($results);

			foreach ($results as $result) {
				$path = '';

				// Admin
				if (substr($result['path'], 0, 6) == 'admin/') {
					$path = DIR_EXTENSION . $directory . $result['path'];
				}

				// Catalog
				if (substr($result['path'], 0, 8) == 'catalog/') {
					$path = DIR_EXTENSION . $directory . $result['path'];
				}

				// Image
				if (substr($result['path'], 0, 6) == 'image/') {
					$path = DIR_IMAGE . substr($result['path'], 6);
				}

				// Config
				if (substr($result['path'], 0, 14) == 'system/config/') {
					$path = DIR_CONFIG . substr($result['path'], 14);
				}

				// Helper
				if (substr($result['path'], 0, 14) == 'system/helper/') {
					$path = DIR_EXTENSION . $directory . $result['path'];
				}

				// Library
				if (substr($result['path'], 0, 15) == 'system/library/') {
					$path = DIR_EXTENSION . $directory . $result['path'];
				}

				// Storage
				if (substr($result['path'], 0, 22) == 'system/storage/vendor/') {
					$path = DIR_STORAGE . 'vendor/' . substr($result['path'], 22);
				}

				if (!file_exists($path)) {
					echo 'Not Found: ' .$path . "\n";
				}

				// Check if the location exists or not
				if (is_file($path)) {
					unlink($path);

					echo 'Deleted File: ' .$path . "\n";
				} elseif (is_dir($path)) {
					rmdir($path);

					echo 'Deleted Dir: ' .$path . "\n";
				}



				//echo $path . "\n";

				$this->model_setting_extension->deletePath($result['extension_path_id']);
			}

			//rmdir(DIR_EXTENSION . $directory);

			$this->model_setting_extension->editStatus($extension_install_id, 0);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete() {
		$this->load->language('marketplace/installer');

		$json = array();

		if (isset($this->request->get['extension_install_id'])) {
			$extension_install_id = $this->request->get['extension_install_id'];
		} else {
			$extension_install_id = 0;
		}

		if (!$this->user->hasPermission('modify', 'marketplace/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$this->load->model('setting/extension');

		$extension_install_info = $this->model_setting_extension->getInstall($extension_install_id);

		if ($extension_install_info) {
			$file = DIR_STORAGE . 'marketplace/' . $extension_install_info['filename'];

			if (!is_file($file)) {
				$json['error'] = $this->language->get('error_file');
			}
		} else {
			$json['error'] = $this->language->get('error_install');
		}

		if (!$json) {
			// Remove file
			unlink($file);

			$this->model_setting_extension->deleteInstall($extension_install_id);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}