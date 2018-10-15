<?php

namespace Drupal\entity_pdf\Controller;

use Mpdf\Config\ConfigVariables;
use Mpdf\Mpdf;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Controller\NodeViewController;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a controller to render a single node.
 */
class PdfNodeController extends NodeViewController {

  /**
   * Public function view.
   */
  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    $build = [
      '#theme' => 'htmlpdf',
      '#title' => parent::title($node),
      '#content' => parent::view($node, $view_mode, $langcode),
    ];

    $output = render($build);

    // If you want the test HTML output, uncomment this:
    // return new Response(render($build), 200, []);

    // Get the filename from config and replace tokens.
    $configFactory = \Drupal::service('config.factory');
    $config = $configFactory->get('entity_pdf.settings');
    $filename = \Drupal::token()->replace($config->get('filename'), [ 'node' => $node ], ['langcode' => $langcode]);

    // Get mpdf's default config and allow other modules to alter it.
    $mpdf_config = [];
    $mpdf_config['tempDir'] = DRUPAL_ROOT . '/sites/default/files/entity_pdf';
    \Drupal::moduleHandler()->alter('mpdf_config', $mpdf_config);

    // Build and return the pdf.
    $mpdf = new Mpdf($mpdf_config);
    $mpdf->SetBasePath(\Drupal::request()->getSchemeAndHttpHost());
    $mpdf->SetTitle($filename);
    $mpdf->WriteHTML($output);
    $content = $mpdf->Output($filename, Destination::INLINE);
    $headers = [
      'Content-Type' => 'application/pdf',
      'Content-disposition' => 'attachment; filename="' . $filename . '"',
    ];
    return new Response($content, 200, $headers);
  }

  /**
   * Public function title.
   *
   * @inheritdoc
   */
  public function title(EntityInterface $node) {
    return parent::title($node);
  }

}
