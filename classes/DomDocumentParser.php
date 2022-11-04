<?php
class DomDocumentParser{
    private $doc;
    

    public function __construct($url) {
        
        $options = array(
            'http' => array('method' => "GET", 'header'=>"User-Agent: khozoonBot/0.1\n")
        );
        $context = stream_context_create($options);

        $this->doc = new DomDocument();
        ini_set('max_execution_time', '3000');
        set_time_limit(3000);
        @$this->doc->loadHTML(file_get_contents($url, false, $context));
        //$this->doc->loadHTML('<?xml encoding="UTF-8">'.file_get_contents($url,false,$context));

    }
    
    public function getlinks() {
        return $this->doc->getElementsByTagName("a");
    }

    public function getTitleTags() {
        return $this->doc->getElementsByTagName("title");
    }

    public function getMetaTags() {
        return $this->doc->getElementsByTagName("meta");
    }

    public function getImage() {
        return $this->doc->getElementsByTagName("img");
    }

}

?>