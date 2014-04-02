<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(TOOLKIT . '/class.datasourcemanager.php');
	require_once(TOOLKIT . '/class.eventmanager.php');
	require_once(TOOLKIT . '/class.pagemanager.php');

	class contentExtensionURLtoPdfPreferences extends AdministrationPage{
		protected $driver;
		public function __viewIndex($context){
						if(file_exists(WORKSPACE.'/assets/css/pdf')){
							$path = scandir(WORKSPACE.'/assets/css/pdf');
						}else{
							$path = scandir(EXTENSIONS.'/urltopdf/assets/css');
						}
						$arraydiffs = array('.','..');
						$arrs = array_diff($path,$arraydiffs);
						foreach($arrs as $dirs => $key){
									$v = explode('.',$key);
									array_pop($v);
									if(array_count_values($v) > 1){
											$a = implode('.',$v);
											$imp[] = array(URL.'/workspace/assets/css/pdf/'.$key,'0',$a);
									}
									else{
											$a = implode($v);
											$imp[] = array(URL.'/workspace/assets/css/pdf/'.$key,'0',$a);
									}
						}
						$this->setPageType('form');
						$form = new XMLElement('form');
						$this->appendSubheading(__('URL to Pdf')); 
						$container = new XMLElement('fieldset');
						$container->setAttribute('class', 'settings');
						$group = new XMLElement('div');
						$group->setAttribute('class', 'two columns');
						$check = file_exists(MANIFEST.'/pdf.config.php');
						$check2 = file_exists(WORKSPACE.'/assets/css/pdf/default.css');
						
						if($check === false){
							$alert = new XMLElement('div');
							$alert->setValue('Message:');
							$msg = new XMLElement('h3');
							$msgtext = 'You must First create the pdf.config.php file in the manifest folder, at the moment the default.css has been selected';
							$msg->setValue($msgtext);
							$alert->appendChild($msg);
							$this->Form->appendChild($alert);
							
						}else{
								if($check2 === true){
										$settingslegend = new XMLElement('legend');
										$settingslegend->setValue('Settings');
										$container->appendChild($settingslegend);
										$div = new XMLElement('div');
										$div->setAttribute('class', 'actions');										
										$sellabel = new XMLElement('label');
										$sellabel->setAttribute('class','column');
										$sellabel->setValue('CSS file');
										$sellabel->appendChild(Widget::Select('template-css',$imp));
										$group->appendChild($sellabel);										
										$container->appendChild($group);									
										$this->Form->appendChild($container);
										$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit'));										
										$this->Form->appendChild($div);
								}
								else{
										$alert = new XMLElement('div');
										$alert->setValue('Message:');
										$msg = new XMLElement('h3');
										$msgtext = 'There are no CSS Sheets to choose from in directory location /workspace/assets/css/pdf/ <br/>';
										$msgtext .=' You must also create a default.css';
										$msg->setValue($msgtext);
										$alert->appendChild($msg);
										$this->Form->appendChild($alert);
								}
						
						}
						
						
						
		}

	

		
	}
