<?php

namespace Drupal\russom_example_xml_format\Encoder;

use Drupal\serialization\Encoder\XmlEncoder as SerializationXMLEncoder;

/**
 * Encodes xml API data.
 *
 * @internal
 */
class RussomXmlEncoder extends SerializationXMLEncoder {

  /**
   * The formats that this Encoder supports.
   * @var string
   */
  protected static $format = ['russom_example_xml'];

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {

    // Symfony/Drupal provides the XML encoding, but need clean it up to remove unwanted nodes
    $parent = parent::encode($data, $format, $context);

    // Load XML again to customize xml structure
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->loadXML($parent);

    // Get <response> node, coming form Symfony/Drupal
    $origElement = $dom->getElementsByTagName("response")[0];

    // Custom alteration for pubmed
    if ($context['view_id'] == 'russom_example_xml_pubmed_serializer') {
      // //Set custom !DOCTYPE
      $implementation = new \DOMImplementation();
      $doctype = $implementation->createDocumentType('ArticleSet PUBLIC "-//NLM//DTD PubMed 2.7//EN" "https://dtd.nlm.nih.gov/ncbi/pubmed/in/PubMed.dtd"');

      // Adds custom <!DOCTYPE> to xml file
      $dom->insertBefore($doctype, $origElement);
    }

    // Custom alteration for linkout
    if ($context['view_id'] == 'russom_example_xml_linkout_serializer') {
      //Set custom !DOCTYPE
      $implementation = new \DOMImplementation();
      $doctype = $implementation->createDocumentType('LinkSet PUBLIC "-//NLM//DTD LinkOut 1.0//EN" "http://www.ncbi.nlm.nih.gov/projects/linkout/doc/LinkOut.dtd" [<!ENTITY icon.url "https://rapidsubmission.cadmus.com/virtual_mentor/virtual_mentor.gif"> <!ENTITY base.url "http://example.org/">]');

      // Adds custom <!DOCTYPE> to xml file
      $dom->insertBefore($doctype, $origElement);
    }

    // Custom alteration for linkout outline
    if ($context['view_id'] == 'russom_example_xml_linkout_outline_serializer') {
      //Set custom !DOCTYPE
      $implementation = new \DOMImplementation();
      $doctype = $implementation->createDocumentType('Provider PUBLIC "-//NLM//DTD LinkOut 1.0//EN" "https://www.ncbi.nlm.nih.gov/projects/linkout/doc/LinkOut.dtd"');

      // Adds custom <!DOCTYPE> to xml file
      $dom->insertBefore($doctype, $origElement);
    }

    // Custom alteration for NLM JATS outline
    if ($context['view_id'] === 'russom_example_xml_nlm_jats_serializer') {
      // Set custom !DOCTYPE
      $implementation = new \DOMImplementation();
      //          $doctype = $implementation->createDocumentType('article PUBLIC "-//NLM//DTD JATS (Z39.96) Journal Publishing DTD v1.2 20190208//EN" "JATS-journalpublishing1.dtd"');
      $doctype = $implementation->createDocumentType('article PUBLIC "-//NLM//DTD JATS (Z39.96) Journal Publishing DTD v1.1 20151215//EN" "JATS-journalpublishing1.dtd"');

      // Adds custom <!DOCTYPE> to xml file
      $dom->insertBefore($doctype, $origElement);
    }

    // Removes wrapping <response>, set by Syfomy/Drupal
    $newParent = $origElement->parentNode;
    foreach ($origElement->childNodes as $child) {
      $newParent->insertBefore($child->cloneNode(TRUE), $origElement);
    }
    $newParent->removeChild($origElement);

    // Removes individual wrapping <item>
    $ItemElement = $dom->getElementsByTagName("item");

    // Loops through each node with <item> wrapped around
    // Symfony/Drupal wraps <item> for each array, undoing the work here.
    while ($ItemElement->length) {
      foreach ($ItemElement as $key => $value) {
        // Gets parent node of <item>
        $newParent = $value->parentNode;

        // foreach child with "item" tag, we dicovered earlier at $ItemElement
        foreach ($value->childNodes as $child) {
          $newParent->insertBefore($child->cloneNode(TRUE), $value);
        }
        $newParent->removeChild($value);
      }
    }

    // Clean up format of xml file after clean-up
    $dom->formatOutput = TRUE;
    $dom->preserveWhiteSpace = FALSE;

    // Save new xml structure
    $new_xml = $dom->saveXML();

    return $new_xml;
  }
}
