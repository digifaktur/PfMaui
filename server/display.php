<?php

// from https://stackoverflow.com/questions/14649645/resize-image-in-php

function resize_image($file, $w, $h, $crop=FALSE) {
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($crop) {
        if ($width > $height) {
            $width = ceil($width-($width*abs($r-$w/$h)));
        } else {
            $height = ceil($height-($height*abs($r-$w/$h)));
        }
        $newwidth = $w;
        $newheight = $h;
    } else {
        if ($w/$h > $r) {
            $newwidth = $h*$r;
            $newheight = $h;
        } else {
            $newheight = $w/$r;
            $newwidth = $w;
        }
    }
    $src = imagecreatefromjpeg($file);
    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    imagejpeg($dst, $file);
}

if ($_SERVER['REQUEST_METHOD'] != 'GET') {
        header('HTTP/1.0 403 Forbidden');
        die();
}
if ($_GET['token'] != 'secret-token') {
    header('HTTP/1.0 403 Forbidden');
    die();
}

$now = new DateTimeImmutable();
//currently active events
$mysqli = new mysqli('HOST', 'USER', 'PASS', 'DB');
$query = 'SELECT * FROM entry WHERE calendar IN (\'basic\', \'second\') AND start < \''.$now->format('Y-m-d H:i:s').'\' AND end > \''.$now->format('Y-m-d H:i:s').'\'';
$current = $mysqli->query($query);

//upcoming events
$query = 'SELECT * FROM entry WHERE calendar IN (\'basic\', \'second\') AND start > \''.$now->format('Y-m-d H:i:s').'\' ORDER BY start ASC LIMIT 5';
$next = $mysqli->query($query);

$mysqli->close();

try {
        $weatherdata = json_decode(file_get_contents('https://api.openweathermap.org/data/2.5/forecast?lat=0&lon=05&appid=YOUR-API-KEY'), true);
}

catch (Exception $ex) { }
$weather = array();
$weather_curr = array();
$day = array();
$dt = new DateTime();
$dt_curr = new DateTime('1970-01-01 12:00:00');

// collect weather data accumulated by day
if (array_key_exists('list', $weatherdata) && count($weatherdata['list']) > 0) {
        foreach($weatherdata['list'] as $datapoint) {
                $dt = new DateTime('@'.$datapoint['dt']);
                if ($dt->format('d') != $dt_curr->format('d')) {
                        if (count($weather_curr) > 0) {
                                $weather[$dt_curr->format('d. m. Y')] = $weather_curr;
                                $weather_curr = array();
                        }
                        $dt_curr = $dt;
                }
                $weather_curr['mintemp'] = (array_key_exists('mintemp', $weather_curr) && intval($weather_curr['mintemp']) < intval($datapoint['main']['temp'])) ? $weather_curr['mintemp'] : intval($datapoint['main']['temp']);
                $weather_curr['maxtemp'] = (array_key_exists('maxtemp', $weather_curr) && intval($weather_curr['maxtemp']) > intval($datapoint['main']['temp'])) ? $weather_curr['maxtemp'] : intval($datapoint['main']['temp']);
                $weather_curr['icon'] = (array_key_exists('icon', $weather_curr) && intval($weather_curr['icon']) > intval(substr($datapoint['weather'][0]['icon'], 0, 2))) ? $weather_curr['icon'] : intval(substr($datapoint['weather'][0]['icon'], 0, 2));

        }
        if (count($weather_curr) > 0) {
                $weather[$dt_curr->format('d. m. Y')] = $weather_curr;
        }
}

// echo HTML

echo '<!doctype HTML>
<html>
<head>
<title>TITLE</title>
</head>
<body style="margin: 0px; padding: 0px; background: #000; color: #DEE; font-family: Arial, Helvetica, sans-serif; font-size: 12pt;">
<div id="kalender" style="position: absolute; top: 0px; left: 0px; padding: 14px; height: 800px; width: 580px; background: #111; ">
<h1 style="font-size: 24pt;">Kalender</h1>
<ul style="list-style: none; font-size: 18pt;">
';
foreach ($current as $event) {
        $cstart = new DateTime($event['start']);
        $cend = new DateTime($event['end']);
        //$event = $row->fetch_assoc();
        echo '<li style="margin-top: 8px;"><span style="font-size: 9pt; color: #AAA;">'.($cstart->format('d.m.Y')).'&ndash;'.($cend->format('d.m.Y')).'</span> '.$event['subject'].'</li>
';
}
foreach ($next as $event) {
        $cut = strpos($event['repeats'], 'GDAY') > 0 ? -9 : -3;
        $cstart = new DateTime($event['start']);
        $formatstring = $cut < -3 ? 'd.m.Y' : 'd.m.Y H:i';
        $color = $event['calendar'] == 'basic' ? 'AAA' : 'FC2';
        //$event = $row->fetch_assoc();
        echo '<li style="margin-top: 8px; color: #'.$color.'"><span style="font-size: 11pt; color: #'.$color.';">'.($cstart->format($formatstring));
        if ($cut < -3) echo '<span style="margin-left: 36px;">&nbsp;</span>';
        echo '</span> '.$event['subject'].'</li>
';
}

echo '
</ul>
';
// fixed weekday event
if ($now->format('w') == 2) {
        echo '<div style="margin: 10px; display: inline-block; text-align: center; font-size: 16pt;"><img src="img/sport.png" alt="Sports" style="max-width: 135px;"><br><span style="color: #7FA;">Heute</span></div>
';
}

foreach ($second as $two) {
        $imgsrc = '';
        $dow = new DateTime($two['start']);
        $wochentage = array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag');
        $wochentag = $wochentage[$dow->format('w')];
        if (strpos($two['subject'], 'test1') > 0) $imgsrc='img1';
        else if (strpos($two['subject'], 'test2') > 0) $imgsrc='imgtwo';
        if ($imgsrc != '') {
                echo '<div style="margin: 10px; display: inline-block; text-align: center; font-size: 16pt;"><img src="img/'.$imgsrc.'.png" alt="'.$imgsrc.'" style="max-width: 175px;"><br>
';
                echo '<span style="color: #EEC;">'.$wochentag.'</span></div>
';
        }

}
echo'</div>
<div id="wetter" style="position: absolute; top: 0px; left: 580px; padding: 14px; height: 400px; width: 700px; background: #012; ">
Wetter:<br>
';
$wcount = 0;
if (count($weather) > 0) {
        foreach($weather as $dday => $wday) {
                $wcount++;
                if ($wcount > 4) break;
                $iconnum = sprintf('%02d', $wday['icon']);
                if (!file_exists('/var/www/html/img/'.$iconnum.'d.png')) {
                        $fh = fopen('/var/www/html/img/'.$iconnum.'d.png', "w");
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, 'https://openweathermap.org/img/wn/'.$iconnum.'d@2x.png');
                        curl_setopt($ch, CURLOPT_FILE, $fh);
                        curl_exec($ch);
                        curl_close($ch);
                        fclose($fh);
                }
                        echo '<div style="display: inline-block; width: 140px; height: 300px; margin: 10px; background: #444; padding-top: 12px; border-radius: 6px; box-shadow: 6px 6px 6px #555;">
<span style="margin-left: 22px;">'.$dday.'</span><br><br><div style="display: inline-block; width: 120px; height: 120px; background: #BCE; border: 2px solid #ABD; border-radius: 4px; box-shadow: 4px 4px 4px #9AC; margin-left: 6px;"><img src="img/'.$iconnum.'d.png" alt="icon" style="width: 100px; margin: 10px;"></div><br><br>
<div style="display: inline-block; width: 120px; margin: 10px; height: 60px; font-size: 20pt; font-weight: bold; text-align: center;">
<span style="color: #AAF;">'.($wday['mintemp']-273).' °C</span><br>
<span style="color: #FAA;">'.($wday['maxtemp']-273).' °C</span></div></div>
';
        }
}
else {
        echo '<br>nicht verf&uuml;gbar';
}
echo'</div>
<div id="bild" style="position: absolute; top: 400px; left: 580px; padding: 14px; height: 400px; width: 700px; background: #201; ">
';

// some random images
$month = intval($now->format('m'));
$files = glob('img/some-string/'.$month.'/*.*');
$file = $files[array_rand($files)];
list($width, $height, $type, $attr) = getimagesize($file);
if ($height > 380) {
        resize_image($file, 700, 380);
}
$file = explode('/', $file);

echo '<img src="img/some-string/'.$month.'/'.$file[count($file) - 1].'" alt="A nice picture" style="max-width: 700px; max-height: 380px; margin: 10px;">
</div>
</body>
</html>
';
?>
