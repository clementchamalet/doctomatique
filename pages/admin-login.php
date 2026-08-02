<?php
require 'config.php';

// If the admin is already logged in, redirect to the dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: espace-admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/inscription.css">
    <style>
        /* Admin page specific styles */
        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            letter-spacing: 0.5px;
        }
        
        .admin-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .login-container.admin {
            border-top: 4px solid #667eea;
            background: linear-gradient(to bottom, rgba(102, 126, 234, 0.02) 0%, white 100%);
        }
        
        .login-header.admin h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .btn-submit.admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .btn-submit.admin:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4291 100%);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            color: #856404;
        }
        
        .warning-box strong {
            display: block;
            margin-bottom: 0.3rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Doctomatique">
                Doctomatique
            </a>
            <div class="nav-links">
                <a href="auth.php" class="btn-connexion">Connexion utilisateur</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="login-container admin">
            <div class="login-header admin">
                <div class="admin-icon">🔐</div>
                <span class="admin-badge">ESPACE ADMINISTRATEUR</span>
                <h1>Connexion Admin</h1>
                <p>Accès réservé aux administrateurs du système</p>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ Zone restreinte</strong>
                Cette page est réservée aux administrateurs autorisés uniquement.
            </div>
            
            <?php
            // Display errors if there are any
            if (isset($_GET['error'])) {
                echo '<div class="message-erreur">';
                switch($_GET['error']) {
                    case 'credentials':
                        echo 'Email ou mot de passe incorrect';
                        break;
                    case 'not_admin':
                        echo 'Accès refusé : vous n\'êtes pas administrateur';
                        break;
                    case 'empty':
                        echo 'Veuillez remplir tous les champs';
                        break;
                    default:
                        echo 'Une erreur est survenue';
                }
                echo '</div>';
            }
            ?>
            
            <form action="traitement-admin-login.php" method="POST" class="login-form">
                <div class="form-group">
                    <label for="email">Email administrateur :</label>
                    <input type="email" name="email" id="email" placeholder="admin@doctomatique.fr" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-submit admin">Se connecter</button>
            </form>
            
            <div class="login-footer">
                <p><a href="auth.php">← Retour à la connexion utilisateur</a></p>
            </div>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Doctomatique</h3>
                <p>La plateforme de santé qui simplifie votre quotidien</p>
            </div>
            
            <div class="footer-section">
                <h4>Patients</h4>
                <a href="help.php">Aide et contact</a>
                <a href="patient.php">Espace client</a>
            </div>
            
            <div class="footer-section">
                <h4>Professionnels</h4>
                <a href="prices.php">Nos solutions</a>
                <a href="professional.php">Espace professionnel</a>
                <a href="admin-login.php">Espace administrateur</a>
                <a href="professional_sec.php">Espace secrétaire</a>
            </div>
            
            <div class="footer-section">
                <h4>Informations</h4>
                <a href="about.php">À propos</a>
                <a href="legal-mentions.php">Mentions légales</a>
                <a href="confidentiality.php">Confidentialité</a>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 Doctomatique. Tous droits réservés.</p>
        </div>
    </footer>
</body>
</html>