<?php
// =====================================
// AUTO-DETECT BASE URL
// =====================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain   = $_SERVER['HTTP_HOST'];
$path     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$site_root_url = $protocol . $domain . ($path ? $path . '/' : '/');
$tools_dir = __DIR__ . '/';
$default_icon = "icon.png";

// =====================================
// FUNCTIONS
// =====================================
function getToolIcon($tool, $default_icon, $base_url) {
    $paths = [
        "$tool/icon.png",
        "$tool/icon1.png",
        "$tool/logo.png",
        "$tool/img/icon.png",
        "$tool/icon/logo.png",
        "$tool/favicon1.ico",
        "$tool/56.png",
        "$tool/112.png",
        "$tool/icons/icon.png"
    ];
    foreach ($paths as $file) {
        if (file_exists(__DIR__ . "/$file")) {
            return $base_url . ltrim($file, './');
        }
    }
    return $base_url . $default_icon;
}

function getToolDescription($tool_dir) {
    $desc = '';
    $meta_path = "$tool_dir/meta.json";
    $desc_path = "$tool_dir/description.txt";

    if (file_exists($meta_path)) {
        $meta = json_decode(file_get_contents($meta_path), true);
        if (!empty($meta['description'])) $desc = $meta['description'];
    } elseif (file_exists($desc_path)) {
        $desc = trim(file_get_contents($desc_path));
    }
    return $desc ?: '✅ X0 Team - X0 Store KaiOS ';
}

function getRandomColor() {
    $colors = ['#FF5733','#33FF57','#3357FF','#FF33A1','#F9C300','#6B5B95'];
    return $colors[array_rand($colors)];
}

// =====================================
// SCAN TOOL FOLDERS
// =====================================
$apps_data = [];
if (is_dir($tools_dir)) {
    foreach (array_diff(scandir($tools_dir), ['.','..']) as $tool) {
        if (is_dir($tools_dir . $tool)) {
            $desc = getToolDescription($tools_dir . $tool);
            $apps_data[] = [
                'name' => $tool,
                'url'  => $site_root_url . ltrim($tool, './') . '/',
                'icon' => getToolIcon($tool, $default_icon, $site_root_url),
                'description' => $desc,
                'updated' => date('c', filemtime($tools_dir . $tool))
            ];
        }
    }
}

// =====================================
// GENERATE apps.json
// =====================================
file_put_contents('apps.json', json_encode($apps_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// =====================================
// GENERATE sitemap.xml
// =====================================
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;
$urlset = $xml->createElement('urlset');
$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$urlset->setAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
$urlset->setAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

$home = $xml->createElement('url');
$home->appendChild($xml->createElement('loc', htmlspecialchars($site_root_url)));
$home->appendChild($xml->createElement('lastmod', date('Y-m-d')));
$urlset->appendChild($home);

foreach ($apps_data as $app) {
    $u = $xml->createElement('url');
    $u->appendChild($xml->createElement('loc', htmlspecialchars($app['url'])));
    $u->appendChild($xml->createElement('lastmod', substr($app['updated'], 0, 10)));

    $news = $xml->createElement('news:news');
    $pub = $xml->createElement('news:publication');
    $pub->appendChild($xml->createElement('news:name', htmlspecialchars($app['name'])));
    $pub->appendChild($xml->createElement('news:language', 'en'));
    $news->appendChild($pub);
    $news->appendChild($xml->createElement('news:title', htmlspecialchars($app['name'])));
    $news->appendChild($xml->createElement('news:publication_date', substr($app['updated'], 0, 10)));
    $u->appendChild($news);

    $img = $xml->createElement('image:image');
    $img->appendChild($xml->createElement('image:loc', htmlspecialchars($app['icon'])));
    $u->appendChild($img);

    $urlset->appendChild($u);
}
$xml->appendChild($urlset);
$xml->save(__DIR__ . '/sitemap.xml');

// =====================================
// GENERATE atom.xml
// =====================================
$atom = new DOMDocument('1.0', 'UTF-8');
$atom->formatOutput = true;

$feed = $atom->createElement('feed');
$feed->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');

$feed->appendChild($atom->createElement('title', 'App Directory Feed'));
$feed->appendChild($atom->createElement('id', htmlspecialchars($site_root_url)));
$feed->appendChild($atom->createElement('updated', date('c')));

foreach ($apps_data as $app) {
    $entry = $atom->createElement('entry');
    $entry->appendChild($atom->createElement('title', htmlspecialchars($app['name'])));
    $entry->appendChild($atom->createElement('id', htmlspecialchars($app['url'])));
    $entry->appendChild($atom->createElement('updated', $app['updated']));

    $link = $atom->createElement('link');
    $link->setAttribute('href', $app['url']);
    $entry->appendChild($link);

    $summary = $atom->createElement('summary', htmlspecialchars($app['description']));
    $entry->appendChild($summary);

    $icon = $atom->createElement('icon', htmlspecialchars($app['icon']));
    $entry->appendChild($icon);

    $content = $atom->createElement('content', htmlspecialchars($app['description']));
    $content->setAttribute('type', 'text');
    $entry->appendChild($content);

    $feed->appendChild($entry);
}

$atom->appendChild($feed);
$atom->save(__DIR__ . '/atom.xml');

// =====================================
// HTML OUTPUT
// =====================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apps - X0 Team ( All Tools )</title>


<meta property="og:title" content="Apps - X0 Team ( All Tools ) ">

<meta property="og:description" content="Apps - X0 Team ( All Tools ) ">

<link rel="manifest" href="manifest.webmanifest"/>

<link rel="manifest" href="manifest.webapp"/>


<link rel="icon" href="icon.png" type="image/x-icon">


<style>
:root {
  --bg:#fafafa; --text:#222; --card:#fff; --focus:#007bff;
}
body.dark { --bg:#121212; --text:#f4f4f4; --card:#333; }
body {
  background:var(--bg); color:var(--text);
  font-family:Arial,sans-serif; margin:0; transition:.3s;
}
.header {
  display:flex; justify-content:center; flex-wrap:wrap;
  gap:8px; padding:10px;
}


button {
  background:var(--card); border:1px solid var(--focus);
  color:var(--focus); border-radius:6px;
  padding:6px 10px; cursor:pointer; transition:.3s;
}


button:hover{background:var(--focus);color:#fff;}


h1{
text-align:center;
margin:10px 0;
}

.container{
  display:grid;
grid-template-columns:repeat(auto-fit,minmax(110px,1fr));
 gap:20px;
padding:20px;
max-width:1200px;
margin:auto;transition:.3s;
}


.container.list{
grid-template-columns:1fr;
}


.tool-box{
  background:var(--card);
border-radius:15px;
padding:20px;
  box-shadow:0 4px 8px rgba(0,0,0,.1);
  text-align:center;
cursor:pointer;
transition:.3s;
}
.tool-box:hover{transform:translateY(-5px);box-shadow:0 8px 18px rgba(0,0,0,.25);}
.tool-box.focused{border:2px solid var(--focus);box-shadow:0 0 15px var(--focus);}
.tool-icon img{
  width:64px;height:64px;border-radius:20%;border:3px solid #ddd;
  transition:border-color .3s;
}
.tool-box:focus .tool-icon img,.tool-box.focused .tool-icon img{border-color:var(--focus);}
.tool-title{font-weight:bold;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.tool-title.small{font-size:.85em;} .tool-title.normal{font-size:1em;}
@media(max-width:600px){.tool-icon img{width:52px;height:52px;}}

</style>



</head>
<body>
<div class="header">
  <button id="darkBtn">🌓 Dark</button>
  <button id="layoutBtn">🔳 List Mode</button>
  <button id="fullBtn">⛶ Fullscreen</button>
</div>
<h1>Apps - X0 Team ( All Tools ) x0.rf.gd</h1>
<div class="container" id="container">
<?php foreach ($apps_data as $app): ?>
  <?php $color=getRandomColor(); ?>
  <div class="tool-box" tabindex="0" data-url="<?= $app['url'] ?>">
    <div class="tool-icon"><img src="<?= $app['icon'] ?>" alt=""></div>
    <a href="<?= $app['url'] ?>" class="tool-title" style="color:<?= $color ?>"><?= htmlspecialchars($app['name']) ?></a>
    <div class="tool-desc"><?= htmlspecialchars($app['description']) ?></div>
  </div>
<?php endforeach; ?>
</div>
<script>
// === ELEMENTS ===
const body=document.body,cont=document.getElementById('container');
const darkBtn=document.getElementById('darkBtn'),
      layoutBtn=document.getElementById('layoutBtn'),
      fullBtn=document.getElementById('fullBtn');

// === RESTORE PREFERENCES ===
if(localStorage.getItem('darkMode')==='true'){body.classList.add('dark');darkBtn.textContent='🌞 Light';}
if(localStorage.getItem('layoutMode')==='list'){cont.classList.add('list');layoutBtn.textContent='🔲 Grid Mode';}
if(localStorage.getItem('fullscreen')==='true' && !document.fullscreenElement){
  document.documentElement.requestFullscreen();
  fullBtn.textContent='❎ Exit Full';
}

// === BUTTON EVENTS ===
darkBtn.onclick=()=>{
  body.classList.toggle('dark');
  const dark=body.classList.contains('dark');
  darkBtn.textContent=dark?'🌞 Light':'🌓 Dark';
  localStorage.setItem('darkMode',dark);
};
layoutBtn.onclick=()=>{
  cont.classList.toggle('list');
  const list=cont.classList.contains('list');
  layoutBtn.textContent=list?'🔲 Grid Mode':'🔳 List Mode';
  localStorage.setItem('layoutMode',list?'list':'grid');
};
fullBtn.onclick=()=>{
  if(!document.fullscreenElement){
    document.documentElement.requestFullscreen();
    fullBtn.textContent='❎ Exit Full';
    localStorage.setItem('fullscreen',true);
  }else{
    document.exitFullscreen();
    fullBtn.textContent='⛶ Fullscreen';
    localStorage.setItem('fullscreen',false);
  }
};

// === KAIOS KEYS ===
const boxes=document.querySelectorAll('.tool-box');
let idx=0;function focusBox(i){boxes.forEach(b=>b.classList.remove('focused'));
  if(boxes[i]){boxes[i].classList.add('focused');boxes[i].focus();}}
focusBox(0);
document.addEventListener('keydown',e=>{
  const cols=Math.max(1,Math.floor(cont.offsetWidth/boxes[0].offsetWidth));
  if(e.key==='ArrowRight')idx=(idx+1)%boxes.length;
  else if(e.key==='ArrowLeft')idx=(idx-1+boxes.length)%boxes.length;
  else if(e.key==='ArrowDown')idx=(idx+cols)%boxes.length;
  else if(e.key==='ArrowUp')idx=(idx-cols+boxes.length)%boxes.length;
  else if(e.key==='Enter')window.location=boxes[idx].dataset.url;
  else if(e.key==='*')darkBtn.click();
  else if(e.key==='#')layoutBtn.click();
  else if(e.key==='f'||e.key==='F')fullBtn.click();
  focusBox(idx);
});
</script>


    
<div id="ad-container" style="border: none; width: 90%; margin: 0 auto; position: relative; visibility: visible; background-color: transparent; overflow: auto;">

 <iframe data-google-container-id="a!2" data-load-complete="true" frameborder="0" height="90" id="ad-iframe" marginheight="0" marginwidth="0" name="ad-iframe" sandbox="allow-forms allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts allow-top-navigation-by-user-activation" scrolling="yes" src="https://googleads.g.doubleclick.net/pagead/ads?client=ca-pub-2089626273805937&amp;output=html&amp;adk=2062069824&amp;adf=3025194257&amp;lmt=1705043566&amp;w=728&amp;rafmt=9&amp;format=728x90&amp;url=https://weiweikev.blogspot.com/&amp;host=ca-host-pub-1556223355139109&amp;" style="border: 0; width: 100%;"></iframe>


    <script src="https://joe-x5.github.io/ads/gads/adsbygoogle.web.js"></script>


</div>
    

<?php
// total-visits.php

// Initialize total visits
$totalVisitsFile = 'total-visits.json';
if (file_exists($totalVisitsFile)) {
    $totalVisitsData = json_decode(file_get_contents($totalVisitsFile), true);
    $totalVisits = $totalVisitsData['totalVisits'] + 1;
} else {
    $totalVisits = 1;
}

// Update total visits
file_put_contents($totalVisitsFile, json_encode(['totalVisits' => $totalVisits]));

// Initialize daily visits
$dailyVisitsFile = 'daily-visits.json';
if (file_exists($dailyVisitsFile)) {
    $dailyVisitsData = json_decode(file_get_contents($dailyVisitsFile), true);
} else {
    $dailyVisitsData = [];
}

$today = date('Y-m-d');
$ip = $_SERVER['REMOTE_ADDR'];

// Get the name of the day for today
$dayName = date('l');

// Check if today's date exists in daily visits
if (!isset($dailyVisitsData[$today])) {
    $dailyVisitsData[$today] = [$dayName => []]; // Create a new array for the day
}

// Increment the visit count for the IP address
if (isset($dailyVisitsData[$today][$dayName][$ip])) {
    $dailyVisitsData[$today][$dayName][$ip]++;
} else {
    $dailyVisitsData[$today][$dayName][$ip] = 1;
}

// Sort dates in descending order
uksort($dailyVisitsData, function($a, $b) {
    return strtotime($b) - strtotime($a);
});

// Update daily visits
file_put_contents($dailyVisitsFile, json_encode($dailyVisitsData, JSON_PRETTY_PRINT));
?>

<div>



<div style="text-align: center;">
  <style>
    @keyframes pulse {
      0% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.1);
      }
      100% {
        transform: scale(1);
      }
    }
    .counter {
      font-size: 24px;
      font-weight: bold;
      color: #333;
      background-color: #f0f0f0;
      padding: 10px;
      border-radius: 5px;
      display: inline-block;
      animation: pulse 2s infinite;
      margin: 10px;
    }
    .counter span {
      font-size: 36px;
      color: #4CAF50;
    }
  </style>
  <p class="counter">Total Views : <span><?php echo $totalVisits; ?></span></p>
  <p class="counter">Today's Users : <span><?php echo isset($dailyVisitsData[$today]) ? count($dailyVisitsData[$today]) : 0; ?></span></p>
</div>




<script src="https://joe-x5.github.io/kaios/notification.js"></script>



<script src="https://joe-x5.github.io/kaios/ban-country.js"></script>


</body>
</html>
