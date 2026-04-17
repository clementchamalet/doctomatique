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
    <title>Politique de Confidentialité - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/confidentiality.css">
</head>
<body>

    <!-- ========== EN-TÊTE DE LA PAGE ========== -->
    <header>
        <nav>
            <!-- Logo principal avec lien vers l'accueil -->
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Logo Doctomatique">
                <span>Doctomatique</span>
            </a>
            
            <!-- Navigation principale -->
            <div class="nav-links">
                <!-- Lien vers l'espace professionnel -->
                <a href="patient.php">Espace patient</a>
                
                <!-- Bouton de connexion -->
                <a href=<?php echo '"' . $link . '"' ; ?> class="btn-connexion"><?php echo $affichage ; ?></a>
            </div>
        </nav>
    </header>

    <!-- ========== CONTENU PRINCIPAL ========== -->
    <main>
        <div class="privacy-container">
            
            <!-- ========== EN-TÊTE DE LA PAGE ========== -->
            <div class="privacy-hero">
                <!-- Titre principal de la page -->
                <h1>Politique de Confidentialité</h1>
                
                <!-- Date de dernière mise à jour -->
                <p class="date">Dernière mise à jour : 10 décembre 2025</p>
            </div>

            <!-- ========== INTRODUCTION ========== -->
            <div class="privacy-intro">
                <p>
                    <strong>Chez Doctomatique, la protection de vos données personnelles est notre priorité absolue.</strong>
                </p>
                
                <p>
                    Cette politique de confidentialité explique comment nous collectons, utilisons, 
                    protégeons et partageons vos informations personnelles conformément au Règlement 
                    Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés.
                </p>
            </div>

            <!-- ========== CONTENU DE LA POLITIQUE ========== -->
            <div class="privacy-content">
                
                <!-- ========== SECTION 1 : RESPONSABLE DU TRAITEMENT ========== -->
                <div class="privacy-section">
                    <h2>1. Responsable du Traitement</h2>
                    
                    <p>
                        Le responsable du traitement des données est :
                    </p>
                    
                    <!-- Encadré d'informations -->
                    <div class="highlight-box">
                        <strong>Doctomatique</strong><br>
                        Association loi 1901 à but non lucratif<br>
                        123 Avenue de la Santé, 75015 Paris, France<br>
                        Email : rgpd@doctomatique.fr
                    </div>
                    
                    <p>
                        Pour toute question concernant vos données personnelles, vous pouvez contacter 
                        notre Délégué à la Protection des Données (DPO) à l'adresse : rgpd@doctomatique.fr
                    </p>
                </div>

                <!-- ========== SECTION 2 : DONNÉES COLLECTÉES ========== -->
                <div class="privacy-section">
                    <h2>2. Données Collectées</h2>
                    
                    <p>
                        Nous collectons uniquement les données nécessaires au fonctionnement de notre service :
                    </p>
                    
                    <!-- Sous-section : Patients -->
                    <h3>Pour les Patients</h3>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type de données</th>
                                <th>Finalité</th>
                                <th>Base légale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Nom, prénom</td>
                                <td>Identification et gestion du compte</td>
                                <td>Contrat</td>
                            </tr>
                            <tr>
                                <td>Email, téléphone</td>
                                <td>Communication, rappels de RDV</td>
                                <td>Contrat</td>
                            </tr>
                            <tr>
                                <td>Date de naissance</td>
                                <td>Vérification d'âge, adaptation du service</td>
                                <td>Contrat</td>
                            </tr>
                            <tr>
                                <td>Adresse postale</td>
                                <td>Localisation des praticiens à proximité</td>
                                <td>Intérêt légitime</td>
                            </tr>
                            <tr>
                                <td>Historique des RDV</td>
                                <td>Gestion des consultations</td>
                                <td>Contrat</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Sous-section : Praticiens -->
                    <h3>Pour les Praticiens</h3>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Type de données</th>
                                <th>Finalité</th>
                                <th>Base légale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Identité complète, numéro RPPS/ADELI</td>
                                <td>Vérification professionnelle</td>
                                <td>Obligation légale</td>
                            </tr>
                            <tr>
                                <td>Adresse du cabinet, coordonnées</td>
                                <td>Mise en relation avec patients</td>
                                <td>Contrat</td>
                            </tr>
                            <tr>
                                <td>Informations bancaires</td>
                                <td>Traitement des paiements</td>
                                <td>Contrat</td>
                            </tr>
                            <tr>
                                <td>Agenda et disponibilités</td>
                                <td>Gestion des rendez-vous</td>
                                <td>Contrat</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Note importante sur les données de santé -->
                    <div class="highlight-box">
                        <strong>⚕️ Données de santé :</strong> Nous ne collectons JAMAIS de données 
                        médicales. Les informations de santé restent confidentielles entre vous et votre praticien.
                    </div>
                </div>

                <!-- ========== SECTION 3 : UTILISATION DES DONNÉES ========== -->
                <div class="privacy-section">
                    <h2>3. Utilisation des Données</h2>
                    
                    <p>
                        Vos données personnelles sont utilisées exclusivement pour :
                    </p>
                    
                    <!-- Liste des utilisations -->
                    <ul>
                        <li><strong>Gestion de votre compte :</strong> Création, authentification et mise à jour de votre profil</li>
                        <li><strong>Prise de rendez-vous :</strong> Faciliter la réservation et la gestion de vos consultations</li>
                        <li><strong>Communication :</strong> Envoi de confirmations, rappels et notifications importantes</li>
                        <li><strong>Amélioration du service :</strong> Analyse anonymisée pour optimiser l'expérience utilisateur</li>
                        <li><strong>Support client :</strong> Traitement de vos demandes d'assistance</li>
                        <li><strong>Sécurité :</strong> Protection contre la fraude et les accès non autorisés</li>
                    </ul>
                </div>

                <!-- ========== SECTION 4 : PARTAGE DES DONNÉES ========== -->
                <div class="privacy-section">
                    <h2>4. Partage des Données</h2>
                    
                    <p>
                        Nous ne vendons jamais vos données personnelles. Vos informations peuvent être 
                        partagées uniquement dans les cas suivants :
                    </p>
                    
                    <!-- Liste des cas de partage -->
                    <ul>
                        <li><strong>Avec les praticiens :</strong> Uniquement les informations nécessaires 
                            à la prise de rendez-vous (nom, coordonnées, historique RDV)</li>
                        <li><strong>Prestataires techniques :</strong> Hébergement, paiement, envoi d'emails 
                            (tous situés dans l'UE et conformes RGPD)</li>
                        <li><strong>Obligations légales :</strong> Si requis par la loi ou une autorité judiciaire</li>
                    </ul>
                    
                    <!-- Note sur le partage avec les praticiens -->
                    <div class="highlight-box">
                        Tous nos partenaires sont soumis à des accords stricts de confidentialité et 
                        ne peuvent utiliser vos données qu'aux fins pour lesquelles nous les leur transmettons.
                    </div>
                </div>

                <!-- ========== SECTION 5 : SÉCURITÉ DES DONNÉES ========== -->
                <div class="privacy-section">
                    <h2>5. Sécurité des Données</h2>
                    
                    <p>
                        Nous mettons en œuvre des mesures de sécurité techniques et organisationnelles 
                        pour protéger vos données :
                    </p>
                    
                    <!-- Liste des mesures de sécurité -->
                    <ul>
                        <li><strong>Chiffrement :</strong> Toutes les communications sont chiffrées</li>
                        <li><strong>Hébergement certifié HDS :</strong> Serveurs sécurisés et conformes aux 
                            normes de santé</li>
                        <li><strong>Sauvegardes quotidiennes :</strong> Vos données sont sauvegardées tous 
                            les jours</li>
                        <li><strong>Accès restreint :</strong> Seules les personnes autorisées peuvent 
                            accéder aux données</li>
                        <li><strong>Surveillance 24/7 :</strong> Nos systèmes sont surveillés en permanence</li>
                        <li><strong>Audits de sécurité :</strong> Tests d'intrusion et audits réguliers</li>
                    </ul>
                </div>

                <!-- ========== SECTION 6 : CONSERVATION DES DONNÉES ========== -->
                <div class="privacy-section">
                    <h2>6. Conservation des Données</h2>
                    
                    <p>
                        Nous conservons vos données pendant les durées suivantes :
                    </p>
                    
                    <!-- Liste des durées de conservation -->
                    <ul>
                        <li><strong>Comptes actifs :</strong> Tant que votre compte est actif</li>
                        <li><strong>Après suppression du compte :</strong> 90 jours (possibilité de récupération)</li>
                        <li><strong>Données de facturation :</strong> 10 ans (obligation légale)</li>
                        <li><strong>Logs de connexion :</strong> 1 an maximum</li>
                        <li><strong>Cookies :</strong> 13 mois maximum</li>
                    </ul>
                </div>

                <!-- ========== SECTION 7 : VOS DROITS ========== -->
                <div class="privacy-section">
                    <h2>7. Vos Droits</h2>
                    
                    <p>
                        Conformément au RGPD, vous disposez des droits suivants :
                    </p>
                    
                    <!-- Liste ordonnée des droits -->
                    <ol>
                        <li><strong>Droit d'accès :</strong> Obtenir une copie de vos données personnelles</li>
                        <li><strong>Droit de rectification :</strong> Corriger vos données inexactes ou incomplètes</li>
                        <li><strong>Droit à l'effacement :</strong> Demander la suppression de vos données</li>
                        <li><strong>Droit à la limitation :</strong> Limiter le traitement de vos données</li>
                        <li><strong>Droit à la portabilité :</strong> Recevoir vos données dans un format structuré</li>
                        <li><strong>Droit d'opposition :</strong> Vous opposer au traitement de vos données</li>
                        <li><strong>Droit de retirer votre consentement :</strong> À tout moment</li>
                    </ol>

                    <!-- Encadré pour exercer ses droits -->
                    <div class="highlight-box">
                        <strong>Pour exercer vos droits :</strong><br>
                        Envoyez un email à rgpd@doctomatique.fr avec une copie de votre pièce d'identité. 
                        Nous vous répondrons sous 30 jours maximum.
                    </div>

                    <p>
                        Vous avez également le droit d'introduire une réclamation auprès de la CNIL 
                        (Commission Nationale de l'Informatique et des Libertés) : www.cnil.fr
                    </p>
                </div>

                <!-- ========== SECTION 8 : COOKIES ========== -->
                <div class="privacy-section">
                    <h2>8. Cookies</h2>
                    
                    <p>
                        Nous utilisons des cookies pour :
                    </p>
                    
                    <!-- Liste des types de cookies -->
                    <ul>
                        <li><strong>Cookies essentiels :</strong> Nécessaires au fonctionnement du site 
                            (session, sécurité)</li>
                        <li><strong>Cookies analytiques :</strong> Statistiques d'utilisation anonymisées 
                            (avec votre consentement)</li>
                    </ul>
                    
                    <p>
                        Vous pouvez gérer vos préférences de cookies à tout moment via les paramètres de 
                        votre navigateur.
                    </p>
                </div>

                <!-- ========== SECTION 9 : TRANSFERTS INTERNATIONAUX ========== -->
                <div class="privacy-section">
                    <h2>9. Transferts Internationaux</h2>
                    
                    <p>
                        <strong>Aucune donnée n'est transférée hors de l'Union Européenne.</strong> 
                        Tous nos serveurs et prestataires sont situés en France ou dans l'UE.
                    </p>
                </div>

                <!-- ========== SECTION 10 : MINEURS ========== -->
                <div class="privacy-section">
                    <h2>10. Mineurs</h2>
                    
                    <p>
                        Notre service est accessible aux mineurs avec le consentement de leurs représentants 
                        légaux. Les parents ou tuteurs peuvent exercer les droits RGPD au nom de leurs enfants 
                        mineurs.
                    </p>
                </div>

                <!-- ========== SECTION 11 : MODIFICATIONS ========== -->
                <div class="privacy-section">
                    <h2>11. Modifications</h2>
                    
                    <p>
                        Nous nous réservons le droit de modifier cette politique de confidentialité. 
                        Toute modification substantielle vous sera notifiée par email 30 jours avant son 
                        entrée en vigueur.
                    </p>
                </div>

                <!-- ========== SECTION 12 : CONTACT ========== -->
                <div class="privacy-section">
                    <h2>12. Contact</h2>
                    
                    <p>
                        Pour toute question concernant cette politique de confidentialité :
                    </p>
                    
                    <!-- Liste des moyens de contact -->
                    <ul>
                        <li>Email : rgpd@doctomatique.fr</li>
                        <li>Courrier : Doctomatique - DPO, 123 Avenue de la Santé, 75015 Paris</li>
                        <li>Téléphone : +33 (0)1 23 45 67 89</li>
                    </ul>
                </div>

            </div>
        </div>
    </main>

    <!-- ========== PIED DE PAGE ========== -->
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