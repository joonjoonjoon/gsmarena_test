<?
// This is a template for a PHP scraper on Morph (https://morph.io)
// including some code snippets below that you should find helpful

// require 'scraperwiki.php';
// require 'scraperwiki/simple_html_dom.php';
//
// // Read in a page
// $html = scraperwiki::scrape("http://foo.com");
//
// // Find something on the page using css selectors
// $dom = new simple_html_dom();
// $dom->load($html);
// print_r($dom->find("table.list"));
//
// // Write out to the sqlite database using scraperwiki library
// scraperwiki::save_sqlite(array('name'), array('name' => 'susan', 'occupation' => 'software developer'));
//
// // An arbitrary query against the database
// scraperwiki::select("* from data where 'name'='peter'")

// You don't have to do things with the ScraperWiki library. You can use whatever is installed
// on Morph for PHP (See https://github.com/openaustralia/morph-docker-php) and all that matters
// is that your final data is written to an Sqlite database called data.sqlite in the current working directory which
// has at least a table called data.


require 'scraperwiki.php';
require 'scraperwiki/simple_html_dom.php';

class GSMAParser {
    
    var $brands = array();
    var $models = 0;
    var $html;
    function init() {
        $this->brands = array();
        $this->models = 0;        
        $this->parseBrands();
        echo count( $this->brands) . ' parsed brands'. "\n";
        
        $this->parseModels();
        echo $this->models . ' parsed models'. "\n";
    }
    function parseBrands(){
        $html_content = scraperwiki::scrape("http://www.gsmarena.com/makers.php3");
        $html = str_get_html($html_content);
    
        $i = 0;
        $temp = array();
        foreach ($html->find("div.st-text a") as $el) {
            
            if($i % 2 == 0){
                $img = $el->find('img',0);
                $b['link'] = 'http://www.gsmarena.com/'.$el->href;
                $b['img'] = $img->src;
                $b['name'] = $img->alt;
                $temp = explode('-',$el->href);
                $b['id'] = (int) substr($temp[2], 0, -4);
                
                $this->brands[] = $b;
                
                scraperwiki::save_sqlite(array("id"=>$b['id']), $b, "cell_brand");
            }           
        
            $i++;
 
        }
        
        $html->__destruct();
    }
    
    function parseModels(){
        $temp = array();
        foreach ($this->brands as $b) {
            
            $this->parseModelsPage($b['id'],$b['name'],$b['link']);
            
        }
    }
    function parseModelsPage($brandId,$brandName,$page){
        $html_content = scraperwiki::scrape($page);
        $this->html = str_get_html($html_content);
        foreach ($this->html->find("div.makers a") as $el) {
            $img = $el->find('img',0);
            $m['name'] = $brandName . ' ' . $el->find('strong',0)->innertext;
            $m['img'] = $img->src;
            $m['link'] = 'http://www.gsmarena.com/'.$el->href;
            $m['desc'] = $img->title;
            $temp = explode('-',$el->href);
            $m['id'] = (int) substr($temp[1], 0, -4);
            $m['brand_id'] = $brandId;
            scraperwiki::save_sqlite(array("id"=>$m['id']), $m, "cell_model");
            $this->models++;
        }
        $pagination = $this->html->find("div.nav-pages",0);
        if($pagination){
           $nextPageLink = $pagination->lastChild();
           if($nextPageLink && $nextPageLink->title=="Next page"){
               $this->parseModelsPage($brandId,$brandName,'http://www.gsmarena.com/'.$nextPageLink->href);
           }
        }
        $this->html->__destruct();
      
    }
}
$parser = new GSMAParser();
$parser->init();
?>