<?php
$base_dir = __DIR__;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));

$replacement = "                <li class=\"dropdown\">\n                    <a href=\"#\" class=\"dropbtn\">Flotte & Missions ⬇</a>\n                    <div class=\"dropdown-content\">\n                        <a href=\"index.php?page=ambulance\">Gestion Ambulances</a>\n                        <a href=\"index.php?page=mission\">Registre Missions</a>\n                    </div>\n                </li>";

$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        
        $content = file_get_contents($path);
        
        // Match the two li elements
        $pattern = '/\s*<li[^>]*><a[^>]*href=["\']index\.php\?page=ambulance["\'][^>]*>.*?<\/a><\/li>\s*<li[^>]*><a[^>]*href=["\']index\.php\?page=mission["\'][^>]*>.*?<\/a><\/li>/is';
        
        $new_content = preg_replace($pattern, "\n" . $replacement, $content);
        
        if ($new_content !== $content) {
            file_put_contents($path, $new_content);
            $count++;
            echo "Regex Replaced in: " . $path . "\n";
        }
    }
}
echo "Total Regex updates: $count\n";
?>
