<?php

require 'config.php' ;

if (isset($_SESSION['user_id'])) {
    $link = 'user-logout.php' ;
    $affichage = 'Se déconnecter' ;
}
else {
    $link = 'auth.php' ;
    $affichage = 'Se connecter' ;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarifs - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/prices.css">
</head>
<body>

    <!-- ========== HEADER ========== -->
    <header>
        <nav>
            <!-- Main logo -->
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>

            <!-- Navigation -->
            <div class="nav-links"> 
                <a href="patient.php">Espace patient</a>
                <a href=<?php echo '"' . $link . '"' ; ?> class="btn-connexion"><?php echo $affichage ; ?></a>
            </div>
        </nav>
    </header>

    <!-- ========== MAIN CONTENT ========== -->
    <main>
        <div class="tarifs-container">

            <!-- Hero -->
            <div class="tarifs-hero">
                <h1>Tarifs Transparents et Justes</h1>
                <p>
                    En tant que plateforme à but non lucratif, nos tarifs couvrent uniquement
                    nos coûts d'infrastructure. Pas de frais cachés, pas de profit.
                </p>
            </div>

            <!-- Transparency banner -->
            <div class="transparency-banner">
                <h2>💚 Notre Engagement de Transparence</h2>
                <p>
                    <strong>Doctomatique est une association à but non lucratif.</strong>
                    Nous ne cherchons pas à faire de profit, mais simplement à maintenir un
                    service de qualité pour tous.
                </p>
                <p>
                    Chaque euro collecté est utilisé pour couvrir nos frais d'hébergement,
                    de serveurs, de maintenance et d'amélioration de la plateforme. Nos
                    comptes sont transparents et disponibles sur demande.
                </p>
            </div>

            <!-- Plans title -->
            <h2 class="section-title">Choisissez Votre Formule</h2>
            <br>

            <!-- Pricing grid -->
            <div class="pricing-grid">

                <!-- Patients -->
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Patients</h3>
                        <p class="subtitle">Accès complet et gratuit</p>
                    </div>

                    <div class="pricing-price">
                        <span class="free-badge">GRATUIT</span>
                    </div>

                    <ul class="pricing-features">
                        <li>Recherche illimitée de praticiens</li>
                        <li>Prise de rendez-vous en ligne</li>
                        <li>Gestion de vos consultations</li>
                        <li>Rappels par email et SMS</li>
                        <li>Historique des rendez-vous</li>
                        <li>Application mobile</li>
                        <li>Support client prioritaire</li>
                    </ul>

                    <div class="pricing-cta">
                        <a href="auth.php" class="btn-submit">Créer mon compte</a>
                    </div>
                </div>

                <!-- Solo practitioner -->
                <div class="pricing-card featured">
                    <div class="pricing-header">
                        <h3>Praticien Solo</h3>
                        <p class="subtitle">Pour les cabinets individuels</p>
                    </div>

                    <div class="pricing-price">
                        <span class="price-amount">19</span>
                        <span class="price-currency">€</span>
                        <div class="price-period">/ mois HT</div>
                    </div>

                    <ul class="pricing-features">
                        <li>Agenda en ligne personnalisable</li>
                        <li>Gestion des rendez-vous illimitée</li>
                        <li>Rappels automatiques aux patients</li>
                        <li>Statistiques et rapports</li>
                        <li>Synchronisation calendrier</li>
                        <li>Support technique dédié</li>
                        <li>30 jours d'essai gratuit</li>
                    </ul>

                    <div class="pricing-cta">
                        <a href="professional.php" class="btn-submit">Essayer gratuitement</a>
                    </div>
                </div>

                <!-- Medical practice -->
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Cabinet Médical</h3>
                        <p class="subtitle">Pour les structures de 2 à 10 praticiens</p>
                    </div>

                    <div class="pricing-price">
                        <span class="price-amount">49</span>
                        <span class="price-currency">€</span>
                        <div class="price-period">/ mois HT</div>
                    </div>

                    <ul class="pricing-features">
                        <li>Tout du plan Solo</li>
                        <li>Jusqu'à 10 praticiens</li>
                        <li>Gestion multi-utilisateurs</li>
                        <li>Agenda partagé et coordination</li>
                        <li>Statistiques avancées</li>
                        <li>Formation à distance incluse</li>
                        <li>Support prioritaire 7j/7</li>
                    </ul>

                    <div class="pricing-cta">
                        <a href="professional.php" class="btn-submit">Essayer gratuitement</a>
                    </div>
                </div>
            </div>

            <!-- Cost table -->
            <div class="cost-breakdown">
                <h2>📊 Répartition détaillée de nos coûts : exemple pour 19€</h2>

                <table class="cost-table">
                    <thead>
                        <tr>
                            <th>Poste de dépense</th>
                            <th>Coût mensuel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Hébergement serveurs (certifié HDS)</td><td>8,50 €</td></tr>
                        <tr><td>Base de données et stockage</td><td>3,20 €</td></tr>
                        <tr><td>Services SMS et emails</td><td>2,80 €</td></tr>
                        <tr><td>Sauvegardes et sécurité</td><td>1,50 €</td></tr>
                        <tr><td>Maintenance et support technique</td><td>2,00 €</td></tr>
                        <tr><td>Développement et améliorations</td><td>1,00 €</td></tr>
                        <tr><td><strong>TOTAL</strong></td><td><strong>19,00 €</strong></td></tr>
                    </tbody>
                </table>

                <p class="cost-note small">
                    <strong>Marge bénéficiaire : 0€</strong> — Nous sommes une association à but non lucratif
                </p>
            </div>

            <!-- Pricing FAQ -->
            <div class="faq-section">
                <h2>Questions Fréquentes sur les Tarifs</h2>

                <div class="faq-grid">
                    <div class="faq-item">
                        <h3>Pourquoi facturer les praticiens ?</h3>
                        <p>Pour maintenir notre infrastructure technique et offrir un service de qualité.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Puis-je annuler à tout moment ?</h3>
                        <p>Oui, aucun engagement. Résiliation possible depuis votre espace professionnel.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Y a-t-il une période d'essai ?</h3>
                        <p>Oui, 30 jours gratuits sans carte bancaire.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Les tarifs vont-ils augmenter ?</h3>
                        <p>Les tarifs sont stables. Toute modification est annoncée 3 mois à l'avance.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Comment sont traités les paiements ?</h3>
                        <p>Paiements sécurisés via Stripe. Aucune donnée bancaire n'est stockée.</p>
                    </div>

                    <div class="faq-item">
                        <h3>Que se passe-t-il si j'arrête ?</h3>
                        <p>Données conservées 90 jours puis supprimées définitivement.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ========== FOOTER ========== -->
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