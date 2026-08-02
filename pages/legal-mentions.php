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
    <title>Mentions Légales - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/legal-mentions.css">
</head>
<body>

    <!-- ========== PAGE HEADER ========== -->
    <header>
        <nav>
            <!-- Main logo linking to home -->
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            
            <!-- Main navigation -->
            <div class="nav-links">
                <!-- Link to the professional area -->
                <a href="patient.php">Espace patient</a>
                
                <!-- Login button -->
                <a href=<?php echo '"' . $link . '"' ; ?> class="btn-connexion"><?php echo $affichage ; ?></a>
            </div>
        </nav>
    </header>

    <!-- ========== MAIN CONTENT ========== -->
    <main>
        <div class="legal-container">
            
            <!-- ========== LEGAL PAGE HEADER ========== -->
            <div class="legal-hero">
                <!-- Main page title -->
                <h1>Mentions Légales</h1>
                
                <!-- Last updated date -->
                <p class="date">Dernière mise à jour : 10 décembre 2025</p>
            </div>

            <!-- ========== LEGAL NOTICE CONTENT ========== -->
            <div class="legal-content">
                
                <!-- ========== SECTION 1: SITE PUBLISHER ========== -->
                <div class="legal-section">
                    <h2>1. Éditeur du Site</h2>
                    
                    <p>
                        Le site web <strong>Doctomatique</strong> (ci-après "le Site") est édité par :
                    </p>
                    
                    <!-- Info box -->
                    <div class="info-box">
                        <p><strong>Doctomatique</strong></p>
                        <p>Association loi 1901 à but non lucratif</p>
                        <p>Siège social : 123 Avenue de la Santé, 75015 Paris, France</p>
                        <p>SIRET : 123 456 789 00012</p>
                        <p>RNA : W751234567</p>
                        <p>Email : contact@doctomatique.fr</p>
                        <p>Téléphone : +33 (0)1 23 45 67 89</p>
                    </div>
                </div>

                <!-- ========== SECTION 2: HOSTING ========== -->
                <div class="legal-section">
                    <h2>2. Hébergement</h2>
                    
                    <p>
                        Le site Doctomatique est hébergé par :
                    </p>
                    
                    <!-- Hosting provider info box -->
                    <div class="info-box">
                        <p><strong>OVH SAS</strong></p>
                        <p>2 rue Kellermann, 59100 Roubaix, France</p>
                        <p>Téléphone : +33 (0)9 72 10 10 07</p>
                        <p>Site web : www.ovh.com</p>
                    </div>
                    
                    <p>
                        Les données de santé sont hébergées sur des serveurs certifiés <strong>HDS 
                        (Hébergeur de Données de Santé)</strong> conformément à l'article L.1111-8 du 
                        Code de la santé publique.
                    </p>
                </div>

                <!-- ========== SECTION 3: INTELLECTUAL PROPERTY ========== -->
                <div class="legal-section">
                    <h2>3. Propriété Intellectuelle</h2>
                    
                    <p>
                        L'ensemble du contenu présent sur le Site (textes, images, graphismes, logo, 
                        icônes, sons, logiciels) est la propriété exclusive de l'association Doctomatique, 
                        à l'exception des marques, logos ou contenus appartenant à d'autres sociétés 
                        partenaires ou auteurs.
                    </p>
                    
                    <p>
                        Toute reproduction, distribution, modification, adaptation, retransmission ou 
                        publication de ces différents éléments est strictement interdite sans l'accord 
                        exprès par écrit de Doctomatique.
                    </p>
                    
                    <p>
                        Le code source de la plateforme est disponible sous licence open source sur notre 
                        dépôt GitHub : <a href="https://github.com/clementchmlt/doctomatique" 
                        style="color: var(--couleur-principale);">github.com/clementchmlt/doctomatique</a>
                    </p>
                </div>

                <!-- ========== SECTION 4: PERSONAL DATA PROTECTION ========== -->
                <div class="legal-section">
                    <h2>4. Protection des Données Personnelles</h2>
                    
                    <p>
                        Conformément au Règlement Général sur la Protection des Données (RGPD) et à la 
                        loi Informatique et Libertés, vous disposez d'un droit d'accès, de rectification, 
                        de suppression et d'opposition aux données personnelles vous concernant.
                    </p>
                    
                    <p>
                        Pour exercer ces droits, vous pouvez nous contacter :
                    </p>
                    
                    <!-- List of contact methods -->
                    <ul>
                        <li>Par email : rgpd@doctomatique.fr</li>
                        <li>Par courrier : Doctomatique - DPO, 123 Avenue de la Santé, 75015 Paris</li>
                    </ul>
                    
                    <p>
                        Pour plus d'informations sur la gestion de vos données personnelles, consultez 
                        notre <a href="confidentiality.php" 
                        style="color: var(--couleur-principale);">Politique de Confidentialité</a>.
                    </p>
                </div>

                <!-- ========== SECTION 5: COOKIES ========== -->
                <div class="legal-section">
                    <h2>5. Cookies</h2>
                    
                    <p>
                        Le Site utilise des cookies pour améliorer l'expérience utilisateur et réaliser 
                        des statistiques de visite anonymes. Ces cookies ne collectent aucune donnée 
                        personnelle identifiable sans votre consentement explicite.
                    </p>
                    
                    <p>
                        Vous pouvez à tout moment modifier vos préférences de cookies via les paramètres 
                        de votre navigateur.
                    </p>
                </div>

                <!-- ========== SECTION 6: LIABILITY ========== -->
                <div class="legal-section">
                    <h2>6. Responsabilité</h2>
                    
                    <p>
                        L'association Doctomatique s'efforce d'assurer l'exactitude et la mise à jour 
                        des informations diffusées sur le Site. Toutefois, elle ne peut garantir 
                        l'exactitude, la précision ou l'exhaustivité des informations mises à disposition.
                    </p>
                    
                    <p>
                        Doctomatique ne saurait être tenue responsable :
                    </p>
                    
                    <!-- List of liability limitations -->
                    <ul>
                        <li>Des erreurs ou omissions dans le contenu du Site</li>
                        <li>Des dommages directs ou indirects résultant de l'utilisation du Site</li>
                        <li>De l'indisponibilité temporaire du Site pour des raisons de maintenance ou techniques</li>
                        <li>Des contenus des sites tiers vers lesquels le Site pourrait rediriger</li>
                    </ul>
                    
                    <p>
                        <strong>Important :</strong> Doctomatique est une plateforme de mise en relation 
                        et de gestion de rendez-vous. Elle n'intervient pas dans la relation médicale entre 
                        le patient et le praticien. Chaque praticien inscrit reste seul responsable de ses 
                        actes médicaux.
                    </p>
                </div>

                <!-- ========== SECTION 7: HYPERLINKS ========== -->
                <div class="legal-section">
                    <h2>7. Liens Hypertextes</h2>
                    
                    <p>
                        Le Site peut contenir des liens hypertextes vers d'autres sites. Doctomatique 
                        n'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à 
                        leur contenu.
                    </p>
                    
                    <p>
                        La création de liens hypertextes vers le Site est autorisée sous réserve qu'ils 
                        ne portent pas atteinte à l'image de Doctomatique et qu'ils respectent les droits 
                        d'auteur.
                    </p>
                </div>

                <!-- ========== SECTION 8: APPLICABLE LAW AND JURISDICTION ========== -->
                <div class="legal-section">
                    <h2>8. Loi Applicable et Juridiction</h2>
                    
                    <p>
                        Les présentes mentions légales sont régies par le droit français. En cas de 
                        litige et à défaut d'accord amiable, le litige sera porté devant les tribunaux 
                        français compétents.
                    </p>
                </div>

                <!-- ========== SECTION 9: MEDIATION ========== -->
                <div class="legal-section">
                    <h2>9. Médiation</h2>
                    
                    <p>
                        Conformément à l'article L.612-1 du Code de la consommation, Doctomatique propose 
                        un dispositif de médiation de la consommation. L'entité de médiation retenue est :
                    </p>
                    
                    <!-- Mediation info box -->
                    <div class="info-box">
                        <p><strong>Centre de médiation de la consommation</strong></p>
                        <p>Email : contact@cm-conso.fr</p>
                        <p>Site web : www.cm-conso.fr</p>
                    </div>
                </div>

                <!-- ========== SECTION 10: CONTACT ========== -->
                <div class="legal-section">
                    <h2>10. Contact</h2>
                    
                    <p>
                        Pour toute question concernant ces mentions légales, vous pouvez nous contacter :
                    </p>
                    
                    <!-- List of contact methods -->
                    <ul>
                        <li>Email : contact@doctomatique.fr</li>
                        <li>Formulaire de contact : <a href="help.php" 
                            style="color: var(--couleur-principale);">Page d'aide</a></li>
                        <li>Téléphone : +33 (0)1 23 45 67 89 (du lundi au vendredi, 9h-18h)</li>
                    </ul>
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