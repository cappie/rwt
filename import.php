<pre>
<?php

function dirToArray($dir) {
    $result = array();

    $cdir = scandir($dir);
    foreach ($cdir as $key => $value) {
        if (!in_array($value,array(".",".."))) {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                $result[] = array('name' => $value, 'children' => dirToArray($dir . DIRECTORY_SEPARATOR . $value));
            } else {
                $result[] = array('name' => $value);
            }
        }
    }
    return $result;
}

function XML2Array(SimpleXMLElement $parent) {
    $array = array();

    foreach ($parent as $name => $element) {
        ($node = & $array[$name])
        && (1 === count($node) ? $node = array($node) : 1)
        && $node = & $node[];

        $node = $element->count() ? XML2Array($element) : trim($element);
    }

    return $array;
}

function importEnglish() {
    require_once('db.php');

    $dir = '../Languages/English/Keyed';
    $files = dirToArray($dir);
    foreach ($files as $file) {
        $filename = $dir.'/'.$file['name'];
        $data = array();
        $lines = file($filename, FILE_IGNORE_NEW_LINES);

        if (is_array($lines)) {
            array_shift($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line!='' && substr($line, 0, 4)!='<!--') {
                    $data[] = $line;
                }
            }

            $xmlstring = implode("\n", $data);

            @$xml = simplexml_load_string($xmlstring);
            if (is_object($xml)) {
                $array = XML2Array($xml);
                $array = array($xml->getName() => $array);
            }

            $returndata[] = array('name'=>$file['name'], 'data'=>$array);
        }
    }

    foreach ($returndata as $file) {
        $parts = explode('.', $file['name']);
        array_pop($parts);
        $origin = implode('.', $parts);
        $data = $file['data']['LanguageData'];

        // insert the origin if it doesn't exist
        $insert_origin = "INSERT IGNORE INTO `origins` SET `name` = '" . $origin . "'";
        $stmt = $dbh->prepare($insert_origin);
        $stmt->execute();

        // get the origin insert ID
        $stmt = $dbh->query("SELECT id FROM `origins` WHERE `name` = '".$origin."'");
        $id = $stmt->fetch(PDO::FETCH_NUM);
        $id = $id[0];

        // insert the string using the origin ID as origin_id
        foreach ($data as $k => $d) {
            $sql = "INSERT IGNORE INTO `strings` SET `origin_id` = '".$id."', `name` = '".$k."', `string` = '".str_replace("'", "\'", $d)."'";
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        }
    }
}

function importLanguage($language) {
    require_once('db.php');

    $dir = '../Languages/'.$language.'/Keyed';
    $files = dirToArray($dir);
    foreach ($files as $file) {
        $filename = $dir.'/'.$file['name'];
        $data = array();
        $lines = file($filename, FILE_IGNORE_NEW_LINES);

        if (is_array($lines)) {
            array_shift($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line!='' && substr($line, 0, 4)!='<!--') {
                    $data[] = $line;
                }
            }

            $xmlstring = implode("\n", $data);

            @$xml = simplexml_load_string($xmlstring);
            if (is_object($xml)) {
                $array = XML2Array($xml);
                $array = array($xml->getName() => $array);
            }

            $returndata[] = array('name'=>$file['name'], 'data'=>$array);
        }
    }

    foreach ($returndata as $file) {
        $parts = explode('.', $file['name']);
        array_pop($parts);
        $origin = implode('.', $parts);
        $data = $file['data']['LanguageData'];

        // get the origin insert ID
        $stmt = $dbh->query("SELECT id FROM `origins` WHERE `name` = '".$origin."'");
        $id = $stmt->fetch(PDO::FETCH_NUM);
        $id = $id[0];

        // insert the string using the origin ID as origin_id
        foreach ($data as $k => $d) {
            $sql = "INSERT IGNORE INTO `strings` SET `origin_id` = '".$id."', `name` = '".$k."', `string` = '".str_replace("'", "\'", $d)."'";
            $stmt = $dbh->prepare($sql);
            $stmt->execute();
        }
    }
}

importEnglish();

?>
</pre>
