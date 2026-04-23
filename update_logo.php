<?php
$base_dir = __DIR__;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));

$replacement = "<div class=\"logo\"><a href=\"index.php\"><img src=\"logo.PNG\" alt=\"MedChain Logo\"></a></div>";

$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        
        $content = file_get_contents($path);
        
        $pattern = '/<div class=["\']logo["\']>\s*<img src=["\']logo\.PNG["\'] alt=["\']MedChain Logo["\']>\s*<\/div>/is';
        
        $new_content = preg_replace($pattern, $replacement, $content);
        
        if ($new_content !== $content) {
            file_put_contents($path, $new_content);
            $count++;
            echo "Logo linked in: " . $path . "\n";
        }
    }
}
echo "Total Logo updates: $count\n";
?>
