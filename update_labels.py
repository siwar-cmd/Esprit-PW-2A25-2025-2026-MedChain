import os

files = [
    r'c:\xampp\htdocs\projet\projet\views\backoffice\bloc-operation\materiel-index.php',
    r'c:\xampp\htdocs\projet\projet\views\backoffice\bloc-operation\materiel-view.php',
    r'c:\xampp\htdocs\projet\projet\views\backoffice\bloc-operation\medecin-materiel-index.php',
    r'c:\xampp\htdocs\projet\projet\views\backoffice\bloc-operation\intervention-create.php',
    r'c:\xampp\htdocs\projet\projet\views\backoffice\bloc-operation\intervention-edit.php'
]

for file_path in files:
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Replacements
        content = content.replace("['nom']", "['id_materiel']")
        content = content.replace(">Nom<", ">ID Matériel<")
        content = content.replace(">Nom du Matériel<", ">ID du Matériel<")
        content = content.replace("Rechercher par nom", "Rechercher par ID")
        
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'Updated {file_path}')
