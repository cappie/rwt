<?php

    require_once('db.php');

    if (!isset($_GET) || count($_GET)<1) {
        header("Location: ".$_SERVER['REQUEST_URI']."?osu=on");
    }

    if (isset($_GET['language'])) {
        $get_lang = addslashes($_GET['language']);
    }

    if (isset($_GET['osu'])) {
        $osu = $_GET['osu'];
    } else {
        $osu = '';
    }

    $stmt = $dbh->query("SELECT * FROM languages WHERE friendlyNameEnglish != 'English'");
    $langs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = $_GET['limit'];
    } else {
        $limit = 3;
    }

    $lang_ids = array();

    if (isset($get_lang) && $get_lang!='') {
        $lang_ids[] = $get_lang;
    } else {
        foreach ($langs as $lang) {
            $lang_ids[] = $lang['id'];
        }
    }

    $num_stats = 0;
    $stats = array();

    foreach ($lang_ids as $l) {
        $sql = "
            SELECT
                (SELECT count(*) FROM strings) AS num_strings,
                (SELECT count(*) FROM translations WHERE language_id = ".$l.") AS num_translations,
                friendlyNameEnglish AS languageEnglish
            FROM languages
            WHERE id = ".$l."
        ";

        $stmt = $dbh->query($sql);
        $nums = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats[$num_stats]['language'] = $nums[0]['languageEnglish'];

        $stats[$num_stats]['translated'] = $nums[0]['num_translations'];
        $stats[$num_stats]['original'] = $nums[0]['num_strings'];

        $stats[$num_stats]['percentage'] = round(ceil(1000 * ($stats[$num_stats]['translated'] / $stats[$num_stats]['original'])) / 10, 1);
        $stats[$num_stats]['percent'] = ceil($stats[$num_stats]['percentage']);
        $num_stats++;
    }

    header('Content-Type: text/html; charset=utf-8');

?>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta charset="utf-8">
    <style type="text/css">
        body {
            font-family:'Open Sans', sans-serif;
            background:#666;
            color:#fff;
            margin:0;
            padding:10px;
        }
        input, textarea, select, option {
            /* font-family:'Open Sans', sans-serif; */
        }
        h1 {
            margin:0;
            padding:0;
        }
        div.ts {
            color:#000;
            display:inline-block;
            margin:15px;
            padding:5px;
            background-color:#ccc !important;
            -webkit-box-shadow: 0px 0px 8px 0px rgba(0,0,0,0.75);
            -moz-box-shadow: 0px 0px 8px 0px rgba(0,0,0,0.75);
            box-shadow: 0px 0px 8px 0px rgba(0,0,0,0.75);
            margin-left:0;
        }
        div.ts label {
            color:#777;
        }
        div.tstring div {
            float:left;vertical-align:top;
        }
        div#bar {
            position:relative;
            width:100%;
            height:2em;
            background-color:#fcc;
            color:#fff;
            margin-bottom:4px;
        }
        div#percentage {
            position:relative;
            background-color:#cfc;
            color:#000;
        }
        div#overlay {
            position:absolute;
            top:0;
            line-height:2em;
            width:100%;
            text-align:center;
            color:#000;
        }
        a { text-decoration:underline; color:#fff; }
        footer { border-top:1px solid #eee; }
    </style>
</head>
<body>
<h2>RimWorld translation tool</h2>
<a href="<?php echo($_SERVER['SCRIPT_NAME']); ?>">HOME</a>
<form id="langselect" name="langselect" method="get" onchange="javascript:this.submit();">
    <label for="language">Translate</label>
    <select name="language">
        <option value=""></option>
        <?php

        foreach ($langs as $lang) {
            if ($get_lang==$lang['id']) { $lang_full = $lang['friendlyNameEnglish']; $s = ' selected'; } else { $s = ''; }
            echo('<option value="'.$lang['id'].'"'.$s.'>'.$lang['friendlyNameNative'].' ('.$lang['friendlyNameEnglish'].')</option>'."\n");
        }

        ?>
    </select>
    <label for="onlyshowuntranslated">only show the first <input type="textbox" name="limit" size="1" value="<?php echo($limit); ?>"> untranslated strings</label><input type="checkbox" name="osu"<?php if ($osu=='on') { echo(' checked'); } ?> />
</form>

<?php

    if (isset($stats) && is_array($stats) && count($stats)>0) {
        foreach ($stats as $stat) {
            if (is_numeric($stat['percentage'])) {
                echo('<div id="bar"><div id="percentage" style="width:'.$stat['percent'].'%;">&nbsp;</div><div id="overlay">'.$stat['language'].' translation '.$stat['percentage'].'% completed ('.$stat['translated'].'/'.$stat['original'].')</div></div>'."\n".'<div style="clear:both;"></div>');
            }
        }
    }

    if (isset($get_lang) && $get_lang!='') {

        if (isset($_POST) && count($_POST)>0) {

            $t_name = addslashes($_POST['string_name']);
            $postid = addslashes($_POST['language_id']);
            $t_language_id = (int)$postid;
            $t_translated_string = addslashes(trim($_POST['translated_string']));

            $stmt = $dbh->prepare("INSERT INTO translations (`string_name`, `language_id`, `translated_string`) VALUES (:stringname, :languageid, :translatedstring)");

            $stmt->bindParam(':stringname', $t_name);
            $stmt->bindParam(':languageid', $t_language_id);
            $stmt->bindParam(':translatedstring', $t_translated_string);

            $stmt->execute();
        }

        if ($osu=='on') {
            $where = " WHERE (t.translated_string = '' OR t.translated_string IS null)";
            $extra = " LIMIT 0, ".$limit;
        } else {
            $where = '';
            $extra = '';
        }
        $sql = "SELECT s.id, s.name, s.string, t.translated_string, o.name as origin_name FROM translations t RIGHT JOIN languages l ON t.language_id = l.id AND l.id = ".$get_lang." RIGHT JOIN strings s ON s.name = t.string_name INNER JOIN origins o ON s.origin_id = o.id".$where." GROUP BY s.name ORDER BY s.id ASC".$extra;

        $stmt = $dbh->query($sql);
        $strings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($strings as $s) {
            if ($osu=='on') {
                if ($s['translated_string']!='') {
                    continue;
                }
            }

            if ($s['translated_string']=='') {
                $bg = "#fcc";
            } else {
                $bg = "#cfc";
            }

            echo('<div class="ts">'."\n");
            echo('  <form id="translate_'.$s['id'].'" name="translate'.$s['id'].'" method="post">'."\n");
            echo('      <input type="hidden" name="id" value="'.$s['id'].'" />'."\n");
            echo('      <input type="hidden" name="language_id" value="'.$get_lang.'" />'."\n");
            echo('      <div style="float:left;">'."\n");
            echo('          <label for="name">String Name</label><br /><input style="background:#ddd;" readonly type="text" name="string_name" value="'.$s['name'].'" size="40" />'."\n");
            echo('      </div>'."\n");
            echo('      <div style="float:right;">'."\n");
            echo('          <label>Origin</label><br /><input style="background:#ddd;" readonly type="text" value="'.$s['origin_name'].'" size="20" />'."\n");
            echo('      </div>'."\n");
            echo('      <div style="clear:both;"></div>'."\n");
            echo('      <div><label>Original English</label><br /><textarea style="background:#ddd;" readonly rows="8" cols="50">'.$s['string'].'</textarea></div>'."\n");
            echo('      <div><label for="translated_string">Translated '.$lang_full.'</label><br /><textarea style="background:'.$bg.';" name="translated_string" rows="8" cols="50">'.$s['translated_string'].'</textarea></div>'."\n");
            echo('      <div><input type="submit" value="save translation ID #'.$s['id'].'" /></div>');
            echo('  </form>'."\n");
            echo('  <div style="clear:both;"></div>'."\n");
            echo('</div>'."\n");
        }
    } else {
        echo('<b>select the language you wish to translate</b>');
    }

?>
</form>
<footer>This tool is in NO way associated with Ludeon Studios | &copy; <?php echo(date("Y")); ?> gamechat.nl</footer>
</body>
</html>
