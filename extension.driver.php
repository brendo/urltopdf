<?php

	Class Extension_URLtoPDF extends Extension {

		public function about() {
			return array(
				'name' => 'URL to PDF',
				'version' => '0.1',
				'release-date' => '2011-07-13',
				'author' => array(
					'name' => 'Brendan Abbott',
					'website' => 'http://www.bloodbone.ws',
					'email' => 'brendan@bloodbone.ws'
				),
				'description' => 'Uses the TCPDF library to take your HTML page and output it as a PDF'
			);
		}

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendOutputPostGenerate',
					'callback' => 'generatePDFfromURL'
				),
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'preferences'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'AdminPagePostGenerate',
					'callback'=> 'postedPreferences'
				)
			);
		}
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> __('Blueprints'),
					'name'		=> __('URL to PDF'),
					'link'		=> '/preferences/'
				)
			);
		}
	/*-------------------------------------------------------------------------
		Delegates:
	-------------------------------------------------------------------------*/

		/**
		 * Generate a PDF from a complete URL
		 */
		public function generatePDFfromURL(array &$context = null) {
			$page_data = Frontend::Page()->pageData();
			if(EXTENSIONS.'/urltofiletype/lib/MPDF56/tmp' == false){
				$director_created = mkdir(EXTENSIONS.'/urltopdf/lib/MPDF56/tmp');
			}
			if(!isset($page_data['type']) || !is_array($page_data['type']) || empty($page_data['type'])) return;

			foreach($page_data['type'] as $type) {
				if($type == 'pdf') {
					// Page has the 'pdf' type set, so lets generate!
					$this->generatePDF($context['output']);
				}
				else if($type == 'pdf-attachment') {
					// Page has the 'pdf-attachment' type set, so lets generate some attachments
					$this->generatePDFAttachments($context['output']);
				}
			}
		}

		public function generatePDF($output) {
			$params = Frontend::Page()->_param;

			$pdf = self::initPDF();

			$pdf->SetAuthor($params['website-name']);
			$pdf->SetTitle($params['page-title']);

			// output the HTML content
			$pdf->writeHTML($output, true, false, true, false, '');

			// reset pointer to the last page
			$pdf->lastPage();

			//Close and output PDF document
			$pdf->Output(sprintf('%s - %s', $params['website-name'], $params['page-title']), 'I');
		}

		public function generatePDFAttachments(&$output) {
			$params = Frontend::Page()->_param;

			$dom = new DOMDocument('1.0', 'UTF-8');
			$doc->formatOutput = true;
			$dom->loadHTML($output);

			if($dom === false) return $output;

			$xpath = new DOMXPath($dom);

			// Copy any <link rel='stylesheet'/> or <style type='text/css'> prepend to the blocks
			$css = '';
			$styling = $xpath->query('//link[@rel="stylesheet"] | //style[@type="text/css"]');
			if($styling->length !== 0) foreach($styling as $style) {
				$css .= $dom->saveXML($style);
			}

			// Find anything with @data-utp attribute set to attachment
			$blocks = $xpath->query('//*[@data-utp = "attachment"]');
			if($blocks->length !== 0) foreach($blocks as $block) {
				// Get the content in those blocks
				$data = $dom->saveXML($block);

				// Send the block to the PDF generator, saving it in /TMP
				$data = $css . $data;
				$pdf = self::initPDF();

				// output the HTML content
				$pdf->writeHTML($data, true, false, true, false, '');

				// reset pointer to the last page
				$pdf->lastPage();

				// get the output of the PDF as a string and save it to a file
				// attempt to find the filename if it's provided with @data-utp-filename
				if(!$filename = $xpath->evaluate('string(//@data-utp-filename)')) {
					 $filename = md5(sprintf('%s - %s', $params['website-name'], $params['page-title']));
				}
				$filename = TMP . '/' . Lang::createFilename($filename) . '.pdf';

				General::writeFile($filename, $pdf->Output($filename, 'S'), Symphony::Configuration()->get('write_mode', 'file'));

				// Replace the attachment node with <link rel='attachment' href='{path/to/file}' />
				$link = $dom->createElement('link');
				$link->setAttribute('rel', 'attachment');
				$link->setAttribute('href', str_replace(DOCROOT, URL, $filename));

				$block->parentNode->replaceChild($link, $block);
			}

			$output = $dom->saveHTML();
		}
		public function postedPreferences( $page , array &$context = null){
				$file = MANIFEST.'/pdf.config.php';
				$check = file_exists($file);
				if($check === true){
						Symphony::Configuration()->flush();
						if(isset($_POST['template-css'])){											
								Symphony::Configuration()->set("path",$_POST['template-css'],"style");
								Symphony::Configuration()->write($file,'755');
						}
						if(isset($_POST['template-file'])){
								Symphony::Configuration()->set("path",$_POST['template-file'],"template");
								Symphony::Configuration()->write($file,'755');
						}
				}	
		}
		private static function initPDF() {
			require_once(EXTENSIONS . '/urltopdf/lib/tcpdf/config/lang/eng.php');
			require_once(EXTENSIONS . '/urltopdf/lib/tcpdf/tcpdf.php');

			// create new PDF document
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

			// set document information
			$pdf->SetCreator(PDF_CREATOR);

			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);

			// set default monospaced font
			$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

			//set margins
			$pdf->SetMargins(20, 20, 20);

			//set auto page breaks
			$pdf->SetAutoPageBreak(TRUE, 20);

			//set image scale factor
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

			//set some language-dependent strings
			$pdf->setLanguageArray($l);

			// add a page
			$pdf->AddPage();

			return $pdf;
		}

	}
