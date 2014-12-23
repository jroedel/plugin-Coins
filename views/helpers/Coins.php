<?php
/**
 * COinS
 *
 * @copyright Copyright 2007-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * @package Coins\View\Helper
 */
class Coins_View_Helper_Coins extends Zend_View_Helper_Abstract
{
    /**
     * Return a COinS span tag for every passed item.
     *
     * @param array|Item An array of item records or one item record.
     * @return string
     */
    public function coins($items)
    {
        if (!is_array($items)) {
            return $this->_getCoins($items);
        }

        $coins = '';
        foreach ($items as $item) {
            $coins .= $this->_getCoins($item);
            release_object($item);
        }
        return $coins;
    }

    /**
     * Build and return the COinS span tag for the specified item.
     *
     * @param Item $item
     * @return string
     */
    protected function _getCoins(Item $item)
    {
        $coins = array();

        $coins['ctx_ver'] = 'Z39.88-2004';
        $coins['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dc';
        $coins['rfr_id'] = 'info:sid/omeka.org:generator';

        // Set the Dublin Core elements that don't need special processing.
        $elementNames = array('Creator', 'Publisher', 'Contributor',
                              'Date', 'Format', 'Source', 'Language', 'Coverage',
                              'Rights', 'Relation','Subject');
        foreach ($elementNames as $elementName) {
            $elementTexts = $this->_getElementTexts($item, $elementName);
            if (false === $elementTexts) {
                continue;
            }

            foreach($elementTexts as $elementText)
            { 
            $elementName = strtolower($elementName);
            $coins["rft.$elementName"][] = $elementText;
            }
        }

        // Handle multiple subjects

              

        // Set the title key from Dublin Core:title
        $titles = $this->_getElementTexts($item, 'Title');
        $itemTypeName = metadata($item, 'item_type_name');
        if ($itemTypeName == 'Artikkeliviite' || $itemTypeName == 'Artikkeli') {
            $subtitle = metadata($item, array('Item Type Metadata', 'Alanimeke'), array('no_filter' => true, 'no_escape' => true, 'snippet' => 500));
        }

        foreach ($titles as $title) {
            if (false === $title) {
                $title = '[unknown title]';
            }
            if ($subtitle) {
                $coins['rft.title'][] = $title . ': ' . $subtitle;
            } 
            else {
              $coins['rft.title'] .= $title;  
            }
        }
        // Set the description key from Dublin Core:description.
        $description = $this->_getElementTexts($item, 'Description',false);
        if (false === $description) {
            return;
        }
        $coins['rft.description'] = $description;

        // Set the type key from item type, map to Zotero item types.
        $itemTypeName = metadata($item, 'item_type_name');
        switch ($itemTypeName) {
            case 'Oral History':
                $type = 'interview';
                break;
            case 'Moving Image':
                $type = 'videoRecording';
                break;
            case 'Sound':
                $type = 'audioRecording';
                break;
            case 'Email':
                $type = 'email';
                break;
            case 'Website':
            case 'Linkki':
                $type = 'webpage';
                break;
            case 'Text':
            case 'Document':
            case 'Kirje':
                $type = 'document';
                break;
            case 'Artikkeliviite':
            case 'Artikkeli':
                $type = 'journalArticle';
                break;
            default:
                if ($itemTypeName) {
                    $type = $itemTypeName;
                } else {
                    $type = $this->_getElementTexts($item, 'Type',false);
                }
        }
        $coins['rft.type'] = $type;

        // Set the issue date key from Dublin Core:Date Issued.
        $dates = $this->_getElementTexts($item, 'Date Issued');
        
        foreach ($dates as $date) {
            $coins['rft.date'][] = $date;
        }

        // Process additional item-type based metadata

        // Porstua Item Type Metadata

        if ($itemTypeName == 'Artikkeliviite' || $itemTypeName == 'Artikkeli') {
            
            $ykl = metadata($item, array('Item Type Metadata', 'YKL'), array('no_filter' => true, 'no_escape' => true, 'snippet' => 500));
            if($ykl) {
                $coins["rft.subject"][] = 'YKL ' . $ykl;
            }
            
            $pages = metadata($item, array('Item Type Metadata', 'Sivunumerot'), array('no_filter' => true, 'no_escape' => true, 'snippet' => 500));
            if($pages) {
              $coins["rft.pages"] = $pages;
            }

            $magazine = metadata($item, array('Item Type Metadata', 'Lehden nimi'), array('no_filter' => true, 'no_escape' => true, 'snippet' => 500));
            if($magazine) {
              $coins["rft.jtitle"] = $magazine;
            }

            $issue = metadata($item, array('Item Type Metadata', 'Lehden numero'), array('no_filter' => true, 'no_escape' => true, 'snippet' => 500));
            if($issue) {
              $coins["rft.issue"] = $issue;
            }


        }

        


        // Set the identifier key as the absolute URL of the current page.
        $coins['rft.identifier'] = absolute_url();

        // Build and return the COinS span tag.
        $coinsSpan = '<span class="Z3988" title="';
        $coinsSpan .= html_escape(http_build_query($coins));
        $coinsSpan .= '"></span>';
        $coinsSpan = preg_replace('/%5B[0-9]+%5D/simU', '', $coinsSpan);
        return $coinsSpan;
        
    }

    /**
     * Get the unfiltered element text for the specified item.
     *
     * @param Item $item
     * @param string $elementName
     * @param bool $all
     * @return string|bool
     */
    protected function _getElementTexts(Item $item, $elementName, $all = true)
    {
        $elementText = metadata(
            $item,
            array('Dublin Core', $elementName),
            array('no_filter' => true, 'no_escape' => true, 'snippet' => 500, 'all' =>$all)
        );
        return $elementText;
    }

    
}
