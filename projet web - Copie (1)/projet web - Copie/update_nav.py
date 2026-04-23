import os

base_dir = r"c:\xampp\htdocs\projet web - Copie (1)\projet web - Copie"

files_to_check = []
for root, dirs, files in os.walk(base_dir):
    for f in files:
        if f.endswith(".php"):
            files_to_check.append(os.path.join(root, f))

target_plural = """                <li><a href="index.php?page=ambulance">Ambulances</a></li>
                <li><a href="index.php?page=mission">Missions</a></li>"""

target_singular = """                    <li><a href="index.php?page=ambulance">Ambulance</a></li>
                    <li><a href="index.php?page=mission">Mission</a></li>"""

repl_plural = """                <li class="dropdown">
                    <a href="#" class="dropbtn">Flotte & Missions ⬇</a>
                    <div class="dropdown-content">
                        <a href="index.php?page=ambulance">Gestion Ambulances</a>
                        <a href="index.php?page=mission">Registre Missions</a>
                    </div>
                </li>"""

repl_singular = """                    <li class="dropdown">
                        <a href="#" class="dropbtn">Flotte & Missions ⬇</a>
                        <div class="dropdown-content">
                            <a href="index.php?page=ambulance">Gestion Ambulances</a>
                            <a href="index.php?page=mission">Registre Missions</a>
                        </div>
                    </li>"""
                    
changed = 0
for filepath in files_to_check:
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            
        new_content = content.replace(target_plural, repl_plural)
        new_content = new_content.replace(target_singular, repl_singular)
        
        if new_content != content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(new_content)
            changed += 1
            print(f"Updated: {os.path.relpath(filepath, base_dir)}")
    except Exception as e:
        print(f"Error reading {filepath}: {e}")

print(f"Total files updated: {changed}")
