<?php
require 'config.php';

// Vérifier que l'utilisateur est connecté en tant qu'admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: admin-login.php');
    exit;
}

$conn = get_db();

// Afficher les messages de succès ou d'erreur
$message = '';
$message_type = '';
if (isset($_GET['success'])) {
    $message_type = 'success';
    switch($_GET['success']) {
        case 'user_updated':
            $message = 'Utilisateur modifié avec succès';
            break;
        case 'user_deleted':
            $message = 'Utilisateur supprimé avec succès';
            break;
        case 'structure_updated':
            $message = 'Structure de santé modifiée avec succès';
            break;
    }
}

if (isset($_GET['error'])) {
    $message_type = 'error';
    switch($_GET['error']) {
        case 'user_not_found':
            $message = 'Utilisateur introuvable';
            break;
        case 'structure_not_found':
            $message = 'Structure de santé introuvable';
            break;
        case 'cannot_delete_admin':
            $message = 'Impossible de supprimer un compte administrateur';
            break;
        default:
            $message = 'Une erreur est survenue';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrateur - Doctomatique</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #438E4C; }
        h2 { color: #333; border-bottom: 2px solid #438E4C; padding-bottom: 10px; margin-top: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .logout-btn { background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .logout-btn:hover { background: #c82333; }
        .section { margin-bottom: 40px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }
        button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-primary { background: #438E4C; color: white; }
        .btn-primary:hover { background: #3a7841; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffc107; color: #333; }
        .btn-warning:hover { background: #e0a800; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #438E4C; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Dashboard Administrateur</h1>
                <p>Connecté en tant que : <strong><?php echo htmlspecialchars($_SESSION['admin_email']); ?></strong></p>
            </div>
            <a href="admin-logout.php" class="logout-btn">Se déconnecter</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <?php
            $sql_count_users = "SELECT COUNT(*) as total FROM utilisateurs";
            $result_users = mysqli_query($conn, $sql_count_users);
            $count_users = mysqli_fetch_assoc($result_users)['total'];
            
            $sql_count_structures = "SELECT COUNT(*) as total FROM structures_sante";
            $result_structures = mysqli_query($conn, $sql_count_structures);
            $count_structures = mysqli_fetch_assoc($result_structures)['total'];
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $count_users; ?></div>
                <div>Utilisateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $count_structures; ?></div>
                <div>Structures de santé</div>
            </div>
        </div>
        
        <h2>Gestion des Utilisateurs</h2>
        
        <div class="section">
            <h3>Modifier ou supprimer un utilisateur</h3>
            <form action="traitement-admin-request.php" method="POST" id="user-form">
                <div class="form-group">
                    <label for="user_email">Email de l'utilisateur concerné :</label>
                    <input type="email" name="user_email" id="user_email" required placeholder="utilisateur@example.com">
                </div>
                
                <div class="form-group">
                    <label for="nouveau_nom">Nouveau nom (laisser vide pour ne pas modifier) :</label>
                    <input type="text" name="nouveau_nom" id="nouveau_nom">
                </div>
                
                <div class="form-group">
                    <label for="nouveau_prenom">Nouveau prénom (laisser vide pour ne pas modifier) :</label>
                    <input type="text" name="nouveau_prenom" id="nouveau_prenom">
                </div>
                
                <div class="form-group">
                    <label for="nouvel_email">Nouvel email (laisser vide pour ne pas modifier) :</label>
                    <input type="email" name="nouvel_email" id="nouvel_email">
                </div>
                
                <div class="btn-group">
                    <button type="submit" name="action" value="update_user" class="btn-primary">Modifier l'utilisateur</button>
                    <button type="submit" name="action" value="delete_user" class="btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible et supprimera toutes les données associées.');">Supprimer l'utilisateur</button>
                </div>
            </form>
        </div>
        
        <h2>Gestion des Structures de Santé</h2>
        
        <div class="section">
            <h3>Modifier une structure de santé</h3>
            <form action="traitement-admin-request.php" method="POST">
                <input type="hidden" name="action" value="update_structure">
                <div class="form-group">
                    <label for="structure_id">ID de la structure :</label>
                    <input type="text" name="structure_id" id="structure_id" required placeholder="1">
                </div>
                <div class="form-group">
                    <label for="structure_nom">Nouveau nom (laisser vide pour ne pas modifier) :</label>
                    <input type="text" name="structure_nom" id="structure_nom">
                </div>
                <div class="form-group">
                    <label for="structure_adresse">Nouvelle adresse (laisser vide pour ne pas modifier) :</label>
                    <input type="text" name="structure_adresse" id="structure_adresse">
                </div>
                <div class="form-group">
                    <label for="structure_telephone">Nouveau téléphone (laisser vide pour ne pas modifier) :</label>
                    <input type="text" name="structure_telephone" id="structure_telephone">
                </div>
                <div class="form-group">
                    <label for="structure_secretaire_id">Nouvel ID secrétaire (laisser vide pour ne pas modifier) :</label>
                    <input type="text" name="structure_secretaire_id" id="structure_secretaire_id">
                </div>
                <button type="submit" class="btn-primary">Modifier la structure</button>
            </form>
        </div>
        
        <div class="section">
            <h3>Liste des structures de santé</h3>
            <?php
            $sql_structures = "SELECT s.id, s.nom, s.adresse, s.telephone, s.secretaire_id, u.prenom, u.nom FROM structures_sante s LEFT JOIN utilisateurs u ON s.secretaire_id = u.id ORDER BY s.nom";
            $result_structures_list = mysqli_query($conn, $sql_structures);
            
            if (mysqli_num_rows($result_structures_list) > 0) {
                echo '<table border="1" cellpadding="10" style="width: 100%; border-collapse: collapse;">';
                echo '<tr style="background: #438E4C; color: white;">
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Adresse</th>
                        <th>Téléphone</th>
                        <th>Secrétaire</th>
                      </tr>';
                while ($structure = mysqli_fetch_assoc($result_structures_list)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($structure['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($structure['nom']) . '</td>';
                    echo '<td>' . htmlspecialchars($structure['adresse']) . '</td>';
                    echo '<td>' . htmlspecialchars($structure['telephone']) . '</td>';
                    echo '<td>' . ($structure['secretaire_id'] ? htmlspecialchars($structure['prenom'] . ' ' . $structure['nom']) : 'Aucun') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p>Aucune structure de santé enregistrée.</p>';
            }
            ?>
        </div>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>