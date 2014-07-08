<?php

	Class Extension_URLtoPDF extends Extension {

	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendOutputPostGenerate',
					'callback' => 'generatePDFfromURL'
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
			if(EXTENSIONS.'/urltopdf/lib/MPDF56/tmp' == false){
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
			
			//$path = EXTENSIONS.'/urltopdf/assets/css/default.css';
			$pdf->SetAuthor($params['website-name']);
			$pdf->SetTitle($params['page-title']);

			// output the HTML content
			//$stylesheet = file_get_contents($path);
			//$pdf->WriteHTML($stylesheet,1);
			$pdf->writeHTML($output);

			//Close and output PDF document
			$pdf->Output(sprintf('%s - %s', $params['website-name'], $params['page-title']), 'I');
		}

		public function generatePDFAttachments(&$output) {
			$params = Frontend::Page()->_param;

			$dom = new DOMDocument('1.0', 'UTF-8');
			//$doc->formatOutput = true;
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

				$pdf->SetAuthor($params['website-name']);
				$pdf->SetTitle($params['page-title']);

				// output the HTML content
				$pdf->writeHTML($data);

				// get the output of the PDF as a string and save it to a file
				// attempt to find the filename if it's provided with @data-utp-filename
				if(!$filename = $xpath->evaluate('string(//@data-utp-filename)')) {
					 $filename = md5(sprintf('%s - %s', $params['website-name'], $params['page-title']));
				}
				$filename = MANIFEST . '/tmp/' . Lang::createFilename($filename) . 'pdf';

				General::writeFile($filename, $pdf->Output($filename, 'S'), Symphony::Configuration()->get('write_mode', 'file'));

				// Replace the attachment node with <link rel='attachment' href='{path/to/file}' />
				$link = $dom->createElement('link');
				$link->setAttribute('rel', 'attachment');
				$link->setAttribute('href', str_replace(DOCROOT, URL, $filename));

				$block->parentNode->replaceChild($link, $block);
			}

			$output = $dom->saveHTML();
		}
		private static function initPDF() {
			require_once(EXTENSIONS . '/urltopdf/lib/MPDF56/mpdf.php');

			// create new PDF document
			$pdf = new mPDF();

			//set margins
			$pdf->SetMargins(20, 20, 20);

			//set auto page breaks
			$pdf->SetAutoPageBreak(TRUE, 20);

			// add a page
			$pdf->AddPage();

			return $pdf;
		}

	}
