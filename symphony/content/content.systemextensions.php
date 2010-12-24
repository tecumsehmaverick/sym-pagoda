<?php

	/**
	 * @package content
	 */

	/**
	 * This page generates the Extensions index which shows all Extensions
	 * that are available in this Symphony installation.
	 */
	require_once(TOOLKIT . '/class.administrationpage.php');

	Class contentSystemExtensions extends AdministrationPage{

		public function __viewIndex(){
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Extensions'))));
			$this->appendSubheading(__('Extensions'));

			$this->Form->setAttribute('action', SYMPHONY_URL . '/system/extensions/');

			$ExtensionManager = Administration::instance()->ExtensionManager;
			$extensions = $ExtensionManager->listAll();

			## Sort by extensions name:
			uasort($extensions, array('ExtensionManager', 'sortByName'));

			$aTableHead = array(
				array(__('Name'), 'col'),
				array(__('Enabled'), 'col'),
				array(__('Version'), 'col'),
				array(__('Author'), 'col'),
			);

			$aTableBody = array();

			if(!is_array($extensions) || empty($extensions)){
				$aTableBody = array(
					Widget::TableRow(array(Widget::TableData(__('None found.'), 'inactive', NULL, count($aTableHead))), 'odd')
				);
			}

			else{
				foreach($extensions as $name => $about){

					## Setup each cell
					$td1 = Widget::TableData((!empty($about['table-link']) && $about['status'] == EXTENSION_ENABLED ? Widget::Anchor($about['name'], Administration::instance()->getCurrentPageURL() . 'extension/' . trim($about['table-link'], '/') . '/') : $about['name']));
					$td2 = Widget::TableData(($about['status'] == EXTENSION_ENABLED ? __('Yes') : __('No')));
					$td3 = Widget::TableData($about['version']);

					if ($about['author'][0] && is_array($about['author'][0])) {
						$value = "";

						for($i = 0; $i < count($about['author']);  ++$i) {
							$author = $about['author'][$i];
							$link = $author['name'];

							if(isset($author['website']))
								$link = Widget::Anchor($author['name'], General::validateURL($author['website']));

							elseif(isset($author['email']))
								$link = Widget::Anchor($author['name'], 'mailto:' . $author['email']);

							$comma = ($i != count($about['author']) - 1) ? ", " : "";
							$value .= $link->generate() . $comma;
						}

						$td4->setValue($value);
					}
					else {
						$link = $about['author']['name'];

						if(isset($about['author']['website']))
							$link = Widget::Anchor($about['author']['name'], General::validateURL($about['author']['website']));

						elseif(isset($about['author']['email']))
							$link = Widget::Anchor($about['author']['name'], 'mailto:' . $about['author']['email']);

						$td4 = Widget::TableData($link);
						
						$td4->appendChild(Widget::Input('items['.$name.']', 'on', 'checkbox'));
					}

					$td4->appendChild(Widget::Input('items['.$name.']', 'on', 'checkbox'));

					## Add a row to the body array, assigning each cell to the row
					$aTableBody[] = Widget::TableRow(array($td1, $td2, $td3, $td4), ($about['status'] == EXTENSION_NOT_INSTALLED ? 'inactive' : NULL));

				}
			}

			$table = Widget::Table(
				Widget::TableHead($aTableHead),
				NULL,
				Widget::TableBody($aTableBody)
			);

			$this->Form->appendChild($table);

			$tableActions = new XMLElement('div');
			$tableActions->setAttribute('class', 'actions');

			$options = array(
				array(NULL, false, __('With Selected...')),
				array('enable', false, __('Enable')),
				array('disable', false, __('Disable')),
				array('uninstall', false, __('Uninstall'), 'confirm'),
			);

			$tableActions->appendChild(Widget::Select('with-selected', $options));
			$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));

			$this->Form->appendChild($tableActions);

		}

		public function __actionIndex(){
			$checked  = @array_keys($_POST['items']);

			if(isset($_POST['with-selected']) && is_array($checked) && !empty($checked)){

				try{
					switch($_POST['with-selected']){

						case 'enable':

							/**
							 * Notifies just before an Extension is to be enabled.
							 *
							 * @delegate ExtensionPreEnable
							 * @since Symphony 2.2
							 * @param string $context
							 * '/system/extensions/'
							 * @param array $extensions
							 *  An array of all the extension name's to be enabled, passed by reference
							 */
							Administration::instance()->ExtensionManager->notifyMembers('ExtensionPreEnable', '/system/extensions/', array('extensions' => &$checked));

							foreach($checked as $name){
								if(Administration::instance()->ExtensionManager->enable($name) === false) return;
							}

							break;

						case 'disable':

							/**
							 * Notifies just before an Extension is to be disabled.
							 *
							 * @delegate ExtensionPreDisable
							 * @since Symphony 2.2
							 * @param string $context
							 * '/system/extensions/'
							 * @param array $extensions
							 *  An array of all the extension name's to be disabled, passed by reference
							 */
							Administration::instance()->ExtensionManager->notifyMembers('ExtensionPreDisable', '/system/extensions/', array('extensions' => &$checked));

							foreach($checked as $name){
								Administration::instance()->ExtensionManager->disable($name);
							}
							break;

						case 'uninstall':

							/**
							 * Notifies just before an Extension is to be uninstalled
							 *
							 * @delegate ExtensionPreUninstall
							 * @since Symphony 2.2
							 * @param string $context
							 * '/system/extensions/'
							 * @param array $extensions
							 *  An array of all the extension name's to be uninstalled, passed by reference
							 */
							Administration::instance()->ExtensionManager->notifyMembers('ExtensionPreUninstall', '/system/extensions/', array('extensions' => &$checked));

							foreach($checked as $name){
								Administration::instance()->ExtensionManager->uninstall($name);
							}

							break;
					}

					redirect(Administration::instance()->getCurrentPageURL());
				}
				catch(Exception $e){
					$this->pageAlert($e->getMessage(), Alert::ERROR);
				}
			}
		}
	}
