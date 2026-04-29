<?php
$base_dir = __DIR__;

$files_to_check = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));
foreach ($iter as $file) {
    if (!$file->isDir() && $file->getExtension() === 'php') {
        $files_to_check[] = $file->getPathname();
    }
}

$target_plural = "                <li><a href=\"index.php?page=ambulance\">Ambulances</a></li>\n                <li><a href=\"index.php?page=mission\">Missions</a></li>";

$target_singular = "                    <li><a href=\"index.php?page=ambulance\">Ambulance</a></li>\n                    <li><a href=\"index.php?page=mission\">Mission</a></li>";

$target_plural2 = "                <li><a href=\"index.php?page=ambulance\">Ambulances</a></li>\r\n                <li><a href=\"index.php?page=mission\">Missions</a></li>";

$target_singular2 = "                    <li><a href=\"index.php?page=ambulance\">Ambulance</a></li>\r\n                    <li><a href=\"index.php?page=mission\">Mission</a></li>";

$repl_plural = "                <li class=\"dropdown\">\n                    <a href=\"#\" class=\"dropbtn\">Flotte & Missions ⬇</a>\n                    <div class=\"dropdown-content\">\n                        <a href=\"index.php?page=ambulance\">Gestion Ambulances</a>\n                        <a href=\"index.php?page=mission\">Registre Missions</a>\n                    </div>\n                </li>";

$repl_singular = "                    <li class=\"dropdown\">\n                        <a href=\"#\" class=\"dropbtn\">Flotte & Missions ⬇</a>\n                        <div class=\"dropdown-content\">\n                            <a href=\"index.php?page=ambulance\">Gestion Ambulances</a>\n                            <a href=\"index.php?page=mission\">Registre Missions</a>\n                        </div>\n                    </li>";
                    
$changed = 0;
foreach ($files_to_check as $filepath) {
    if(basename($filepath) == 'update_nav.php') continue;
    $content = file_get_contents($filepath);
    
    $new_content = str_replace([$target_plural, $target_plural2], $repl_plural, $content);
    $new_content = str_replace([$target_singular, $target_singular2], $repl_singular, $new_content);
    
    if ($new_content !== $content) {
        file_put_contents($filepath, $new_content);
        $changed++;
        echo "Updated: " . str_replace($base_dir, '', $filepath) . "\n";
    }
}
echo "Total files updated: $changed\n";
?>
