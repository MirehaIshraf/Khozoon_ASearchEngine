<?php
include ("config.php");
include("classes/DomDocumentParser.php");

$alreadyCrawled = array();
$crawling = array();
$alreadyFoundImages = array();

function linkExists($url) {
    global $con;

    $query = $con->prepare("SELECT * FROM sites WHERE url = :url");
    
    $query->bindParam(":url", $url);
    $query->execute();

    return $query->rowCount() != 0;
} 

function insertLink($url, $title, $description, $keywords) {
    global $con;

    $query = $con->prepare("INSERT INTO sites(url, title, description, keywords)
                            VALUES(:url, :title, :description, :keywords)");
    
    $query->bindParam(":url", $url);
    $query->bindParam(":title", $title);
    $query->bindParam(":description", $description);
    $query->bindParam(":keywords", $keywords);

    
    return $query->execute();

}
function insertImage($url, $src, $title, $alt) {
    global $con;

    $query = $con->prepare("INSERT INTO images(siteUrl, imageUrl, alt, title)
                            VALUES(:siteUrl, :imageUrl, :alt, :title)");
    
    $query->bindParam(":siteUrl", $url);
    $query->bindParam(":imageUrl", $src);
    $query->bindParam(":alt", $alt);
    $query->bindParam(":title", $title);

    return $query->execute();

}

function createLink($src, $url){
    $scheme = parse_url($url)["scheme"]; //http
    $host = parse_url($url)["host"]; //www.reecekenney.com

    if(substr($src, 0, 2) == "//") {
        $src = $scheme . ":" . $src;
    }
    else if(substr($src, 0, 1) == "/"){
        $src = $scheme ."://". $host . $src;
    }
    else if(substr($src, 0, 2) == "./") {
        $src = $scheme . "://" .$host . dirname(parse_url($url)["path"]) . substr($src, 1);
    }
    else if(substr($src, 0, 3) == "../") {
        $src = $scheme . "://" .$host . "/" . $src;
    }
    else if(substr($src, 0, 5) != "https" && substr($src, 0, 4) != "http") {
        $src = $scheme . "://" .$host . "/" . $src;
    }


    return $src;

}

function getDetails($url) {
    $parser = new DomDocumentParser($url);

    $titleArray = $parser->getTitleTags();
    $metasArray = $parser->getMetaTags();

    if(sizeof($titleArray) == 0 || $titleArray->item(0) == NULL){
        return;
    }

    $title = $titleArray->item(0)->nodeValue;
    $title = str_replace("\n","",$title);

    if($title == ""){
        return;
    }

    $description = "";
    $keywords = "";

    foreach($metasArray as $meta){
        if($meta->getAttribute("name") == "description"){
            $description = $meta->getAttribute("content");
        } 
        
        if($meta->getAttribute("name") == "keywords"){
            $keywords = $meta->getAttribute("content"); 
        }
    }
    
    $description = str_replace("\n","",$description);
    $keywords = str_replace("\n","",$keywords);

    if(linkExists($url)) {
        echo "$url already exists <br>";
    } 
    else if(insertLink($url, $title, $description, $keywords)) {
        echo "SUCCESS: $url <br>"; 
    }
    else {
        echo "ERROR: Failed to insert $url <br>";
    }


    //imageDetails
    global $alreadyFoundImages;
    $imageArray = $parser->getImage();

    foreach($imageArray as $image) {
        $src = $image->getAttribute("src");
        $alt = $image->getAttribute("alt");
        $title = $image->getAttribute("title");

        if(!$title and !$alt) {
            continue;
        }

        $src = createLink($src, $url);

        if(!in_array($src, $alreadyFoundImages)) {
            $alreadyFoundImages[] = $src;

            //insert the image
            insertImage($url, $src, $title, $alt);
        }
    }
    

    //insertLink($url, $title, $description, $keywords);
    //echo "URL: $url, <br> Description: $description,<br> keywords: $keywords <br>";
}
$first = 0;
function followLinks($url){
    global $first;
    global $alreadyCrawled;
    global $crawling;

    $parser = new DomDocumentParser($url);

    $linkList = $parser->getlinks();
    $c = 0;
    foreach($linkList as $link) {
        $c++;
        // if($first == 1)
        // {
        //     if($c > 5){
        //         break;
        //     }
        // }
        $href = $link->getAttribute("href");

        if(strpos($href, "#") !== false){
            continue;
        }
        else if(substr($href, 0, 11) == "javascript:"){
            continue;
        }

        $href = createLink($href, $url);

        if(!in_array($href, $alreadyCrawled)){
            $alreadyCrawled[] = $href;
            $crawling[] = $href;

            getDetails($href);
            //Insert $href
        }
    }

    $first=1;
    array_shift($crawling);
    echo "sites visited: $c <br> ";

    foreach($crawling as $site){
        followLinks($site);
    }

}

//$startUrl = "https://www.netflix.com/bd/title/81318083";
//$startUrl = "https://en.wikipedia.org";
$startUrl = "https://www.bbc.com/sport/football";
//$startUrl = "https://www.pexels.com/";
//$startUrl = "https://unsplash.com/s/photos/free";
//$startUrl = "https://www.pbs.org";
//$startUrl = "https://www.bbc.com";
//$randomUrl = "https://www.bbc.co.uk";
followLinks($startUrl);
//getDetails($startUrl);
//getDetails($randomUrl);
?>