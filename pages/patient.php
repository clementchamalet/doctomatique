<?php
require 'config.php';
require 'check-connexion.php';

$conn = get_db();

$user_id = $_SESSION['user_id'];

// Get the user's info
$sql_user = "SELECT nom, prenom, email, telephone FROM utilisateurs WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $sql_user);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user_info = mysqli_fetch_assoc($result_user);
mysqli_stmt_close($stmt_user);

// Get the patient ID (from the patients table)
$sql_patient_id = "SELECT utilisateur_id FROM patients WHERE utilisateur_id = ?";
$stmt_patient_id = mysqli_prepare($conn, $sql_patient_id);
mysqli_stmt_bind_param($stmt_patient_id, "i", $user_id);
mysqli_stmt_execute($stmt_patient_id);
$result_patient_id = mysqli_stmt_get_result($stmt_patient_id);
$patient_id = null;
if (mysqli_num_rows($result_patient_id) > 0) {
    $patient_id = mysqli_fetch_assoc($result_patient_id)['utilisateur_id'];
}
mysqli_stmt_close($stmt_patient_id);

// Get upcoming appointments
$sql_rdv_futurs = "SELECT 
    r.id as rdv_id,
    r.cree_le,
    s.description as statut,
    m.description as motif,
    cd.debut as date_debut,
    cd.duree_minute,
    u_prat.nom as praticien_nom,
    u_prat.prenom as praticien_prenom,
    st.nom as structure_nom,
    st.adresse as structure_adresse
FROM rendezvous r
INNER JOIN creneaux_disponibilite cd ON r.creneaux_id = cd.id
INNER JOIN praticiens p ON cd.praticien_id = p.utilisateur_id
INNER JOIN utilisateurs u_prat ON p.utilisateur_id = u_prat.id
LEFT JOIN praticiens_structures ps ON p.utilisateur_id = ps.praticien_id
LEFT JOIN structures_sante st ON ps.structure_id = st.id
LEFT JOIN statut s ON r.statut = s.id
LEFT JOIN motif m ON r.motif = m.id
WHERE r.patient_id = ? AND cd.debut >= NOW()
ORDER BY cd.debut ASC";

$stmt_rdv_futurs = mysqli_prepare($conn, $sql_rdv_futurs);
mysqli_stmt_bind_param($stmt_rdv_futurs, "i", $patient_id);
mysqli_stmt_execute($stmt_rdv_futurs);
$result_rdv_futurs = mysqli_stmt_get_result($stmt_rdv_futurs);

$rdv_futurs = [];
while ($row = mysqli_fetch_assoc($result_rdv_futurs)) {
    $rdv_futurs[] = $row;
}
mysqli_stmt_close($stmt_rdv_futurs);

// Get past appointments with consultation notes
$sql_rdv_passes = "SELECT 
    r.id as rdv_id,
    r.cree_le,
    s.description as statut,
    m.description as motif,
    cd.debut as date_debut,
    cd.duree_minute,
    u_prat.nom as praticien_nom,
    u_prat.prenom as praticien_prenom,
    st.nom as structure_nom,
    st.adresse as structure_adresse,
    nc.notes as notes_consultation
FROM rendezvous r
INNER JOIN creneaux_disponibilite cd ON r.creneaux_id = cd.id
INNER JOIN praticiens p ON cd.praticien_id = p.utilisateur_id
INNER JOIN utilisateurs u_prat ON p.utilisateur_id = u_prat.id
LEFT JOIN praticiens_structures ps ON p.utilisateur_id = ps.praticien_id
LEFT JOIN structures_sante st ON ps.structure_id = st.id
LEFT JOIN notes_consultation nc ON r.id = nc.rendezvous_id
LEFT JOIN statut s ON r.statut = s.id
LEFT JOIN motif m ON r.motif = m.id
WHERE r.patient_id = ? AND cd.debut < NOW()
ORDER BY cd.debut DESC";

$stmt_rdv_passes = mysqli_prepare($conn, $sql_rdv_passes);
mysqli_stmt_bind_param($stmt_rdv_passes, "i", $patient_id);
mysqli_stmt_execute($stmt_rdv_passes);
$result_rdv_passes = mysqli_stmt_get_result($stmt_rdv_passes);

$rdv_passes = [];
while ($row = mysqli_fetch_assoc($result_rdv_passes)) {
    $rdv_passes[] = $row;
}
mysqli_stmt_close($stmt_rdv_passes);

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Patient - Doctomatique</title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/patient.css">
</head>
<body>
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <img src="../assets/images/logo.png" alt="Doctomatique">
                Doctomatique
            </a>
            
            <div class="header-center">
                <h1>Espace Patient</h1>
                <p>Bienvenue, <?php echo htmlspecialchars($user_info['prenom'] . ' ' . $user_info['nom']); ?></p>
            </div>
            
            <div class="nav-links">
                <a href="user-logout.php" class="btn-retour">Se déconnecter</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <!-- Personal information section -->
            <section>
                <h2>Mes informations personnelles</h2>
                <table>
                    <tbody>
                        <tr>
                            <th>Nom</th>
                            <td><?php echo htmlspecialchars($user_info['nom']); ?></td>
                        </tr>
                        <tr>
                            <th>Prénom</th>
                            <td><?php echo htmlspecialchars($user_info['prenom']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($user_info['email']); ?></td>
                        </tr>
                        <tr>
                            <th>Téléphone</th>
                            <td><?php echo htmlspecialchars($user_info['telephone']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- Upcoming appointments section -->
            <section>
                <h2>Mes rendez-vous à venir</h2>
                <?php if (count($rdv_futurs) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Durée</th>
                                <th>Praticien</th>
                                <th>Structure</th>
                                <th>Motif</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rdv_futurs as $rdv): ?>
                                <tr>
                                    <td data-label="Date"><?php echo date('d/m/Y', strtotime($rdv['date_debut'])); ?></td>
                                    <td data-label="Heure"><?php echo date('H:i', strtotime($rdv['date_debut'])); ?></td>
                                    <td data-label="Durée"><?php echo $rdv['duree_minute']; ?> min</td>
                                    <td data-label="Praticien">Dr. <?php echo htmlspecialchars($rdv['praticien_prenom'] . ' ' . $rdv['praticien_nom']); ?></td>
                                    <td data-label="Structure">
                                        <?php if ($rdv['structure_nom']): ?>
                                            <strong><?php echo htmlspecialchars($rdv['structure_nom']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($rdv['structure_adresse']); ?></small>
                                        <?php else: ?>
                                            <em>Non renseigné</em>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Motif"><?php echo htmlspecialchars($rdv['motif'] ?? 'Non renseigné'); ?></td>
                                    <td data-label="Statut"><?php echo htmlspecialchars($rdv['statut'] ?? 'Confirmé'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Vous n'avez aucun rendez-vous à venir.</p>
                <?php endif; ?>
            </section>

            <!-- Past appointments section -->
            <section>
                <h2>Mes rendez-vous passés</h2>
                <?php if (count($rdv_passes) > 0): ?>
                    <?php foreach ($rdv_passes as $rdv): ?>
                        <div class="rdv-card">
                            <h3>Rendez-vous du <?php echo date('d/m/Y à H:i', strtotime($rdv['date_debut'])); ?></h3>
                            
                            <p><strong>Praticien :</strong> Dr. <?php echo htmlspecialchars($rdv['praticien_prenom'] . ' ' . $rdv['praticien_nom']); ?></p>
                            
                            <?php if ($rdv['structure_nom']): ?>
                                <p><strong>Structure :</strong> <?php echo htmlspecialchars($rdv['structure_nom']); ?></p>
                                <p><strong>Adresse :</strong> <?php echo htmlspecialchars($rdv['structure_adresse']); ?></p>
                            <?php endif; ?>
                            
                            <p><strong>Durée :</strong> <?php echo $rdv['duree_minute']; ?> minutes</p>
                            
                            <?php if ($rdv['motif']): ?>
                                <p><strong>Motif :</strong> <?php echo htmlspecialchars($rdv['motif']); ?></p>
                            <?php endif; ?>
                            
                            <p><strong>Statut :</strong> <?php echo htmlspecialchars($rdv['statut'] ?? 'Terminé'); ?></p>
                            
                            <?php if ($rdv['notes_consultation']): ?>
                                <div class="notes-consultation">
                                    <h4>Notes de consultation</h4>
                                    <p><?php echo nl2br(htmlspecialchars($rdv['notes_consultation'])); ?></p>
                                </div>
                            <?php else: ?>
                                <p><em>Aucune note de consultation disponible.</em></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Vous n'avez aucun rendez-vous passé.</p>
                <?php endif; ?>
            </section>
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