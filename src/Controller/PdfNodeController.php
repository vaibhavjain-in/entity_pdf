<?php

namespace Drupal\entity_pdf\Controller;

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

    $config = [
      'tempDir' => DRUPAL_ROOT . '/sites/default/files/entity_pdf',
      'mode' => 'utf-8',
      'format' => 'A4',
      'margin_left' => 0,
      'margin_right' => 0,
      'margin_top' => 0,
      'margin_bottom' => 0,
      'margin_header' => 0,
      'margin_footer' => 0,
    ];
    if (\Drupal::config('entity_pdf.settings')->get('bc') == 8001) {
      $config = [
        'tempDir' => DRUPAL_ROOT . '/sites/default/files/entity_pdf',
      ];
    }

    $mpdf = new Mpdf($config);
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
