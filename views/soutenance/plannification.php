<?php require_once __DIR__ . '/../sidebar.html'; ?>


<style>
    body {
        background: #f4f6f9;
        font-family: 'DM Sans', sans-serif;
    }
    .main-content {
        margin-left: 260px;
        padding: 40px;
        min-height: 100vh;
    }
    .planning-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        max-width: 850px;
        margin: auto;
    }
    .planning-title {
        font-size: 2rem;
        font-weight: 700;
        color: #23242c;
        margin-bottom: 10px;
    }
    .planning-subtitle {
        color: #6c757d;
        margin-bottom: 35px;
        font-size: 0.95rem;
    }
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #23242c;
        margin-bottom: 15px;
    }
    .input-group-modern {
        display: flex;
        flex-direction: column;
        margin-bottom: 30px;
    }
    .input-group-modern label {
        margin-bottom: 8px;
        font-weight: 600;
        color: #495057;
    }
    .input-modern {
        border: 1px solid #dcdfe4;
        border-radius: 14px;
        padding: 14px;
        font-size: 15px;
        transition: 0.3s;
        background: #fafafa;
        max-width: 320px;
    }
    .input-modern:focus {
        outline: none;
        border-color: #375e69;
        box-shadow: 0 0 0 4px rgba(55,94,105,0.15);
        background: #fff;
    }

    /* Aperçu des 3 jours générés */
    .jours-preview {
        display: flex;
        gap: 12px;
        margin-top: 16px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    .jour-badge {
        background: rgba(55,94,105,0.08);
        border: 1px dashed #375e69;
        border-radius: 12px;
        padding: 10px 18px;
        font-size: 0.88rem;
        color: #375e69;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .jour-badge i { font-size: 14px; }

    .info-box {
        background: #f0f7f9;
        border-left: 4px solid #375e69;
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 0.9rem;
        color: #4a6572;
        margin-bottom: 30px;
        display: flex;
        gap: 10px;
        align-items: flex-start;
    }
    .info-box i { margin-top: 2px; color: #375e69; }

    .btn-planification {
        background: linear-gradient(135deg, #375e69, #4d7d8b);
        color: white;
        border: none;
        border-radius: 14px;
        padding: 15px 30px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        width: 100%;
    }
    .btn-planification:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(55,94,105,0.25);
    }
    .planning-icon {
        width: 70px;
        height: 70px;
        background: rgba(55,94,105,0.1);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 25px;
    }
    .planning-icon i {
        font-size: 32px;
        color: #375e69;
    }
    @media(max-width: 768px) {
        .main-content { margin-left: 0; padding: 20px; }
        .planning-card { padding: 25px; }
        .input-modern { max-width: 100%; }
    }
</style>

<div class="main-content">
    <div class="planning-card">

        <div class="planning-icon">
            <i class="bi bi-calendar2-check"></i>
        </div>

        <h2 class="planning-title">Planification automatique</h2>
        <p class="planning-subtitle">
            Génération intelligente des soutenances sur 3 jours consécutifs,
            avec répartition équitable par filière et affectation automatique des jurys.
        </p>

        <form method="POST" action="/projetweb/index.php?controller=finale" id="formPlanif">

    <div class="section-title">Paramètres des soutenances</div>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div class="input-group-modern">
            <label for="date_debut">
                <i class="bi bi-calendar-event"></i> Date de début
            </label>
            <input type="date" id="date_debut" name="date_debut"
                   class="input-modern" required
                   min="<?= date('Y-m-d') ?>"
                   onchange="recalcul()">
        </div>

        <div class="input-group-modern">
            <label for="nb_jours">
                <i class="bi bi-calendar-plus"></i> Nombre de jours
            </label>
            <input type="number" id="nb_jours" name="nb_jours"
                   class="input-modern" value="3" min="1" max="30"
                   oninput="recalcul()">
            <small id="min-hint" style="color:#6c757d; margin-top:6px; font-size:0.82rem;"></small>
        </div>
    </div>

    <!-- Aperçu dynamique des jours -->
    <div id="jours-preview" class="jours-preview" style="display:none;"></div>

    <?php if (!empty($warning)): ?>
        <div class="alert alert-warning">
            <?= htmlspecialchars($warning) ?>
        </div>
    <?php endif; ?>
    

    <div class="info-box">
        <i class="bi bi-info-circle-fill"></i>
        <span>
            Les soutenances de <strong>tous les départements</strong> seront réparties
            équitablement. Durée par soutenance : <strong>30 min</strong>,
            pause min. <strong>60 min</strong> entre deux passages pour un même jury.
        </span>
    </div>

    <button type="submit" class="btn-planification" id="btn-planif">
        <i class="bi bi-magic"></i>
        Générer automatiquement le planning
    </button>
</form>



<script>
// À adapter selon votre base : nombre total de soutenances, salles, séances/jour
const NB_SOUTENANCES_TOTAL = <?= $nbSoutenances ?>; // passer depuis le contrôleur
const NB_SALLES            = <?= $nbSalles ?>;       // idem
const NB_SEANCES_PAR_JOUR  = <?= $nbSeancesJour ?>;  // idem

function recalcul() {
    const dateVal = document.getElementById('date_debut').value;
    const nbJours = parseInt(document.getElementById('nb_jours').value) || 0;

    const capacite = nbJours * NB_SALLES * NB_SEANCES_PAR_JOUR;
    const minJours = Math.ceil(NB_SOUTENANCES_TOTAL / (NB_SALLES * NB_SEANCES_PAR_JOUR));

    document.getElementById('min-hint').textContent =
        `Minimum requis : ${minJours} jour${minJours > 1 ? 's' : ''}`;

    // Aperçu des jours
    const preview = document.getElementById('jours-preview');
    if (dateVal && nbJours > 0) {
        const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        let html = '';
        for (let i = 0; i < nbJours; i++) {
            const d = new Date(dateVal + 'T00:00:00');
            d.setDate(d.getDate() + i);
            html += `<div class="jour-badge">
                        <i class="bi bi-calendar-day"></i>
                        Jour ${i+1} : ${d.toLocaleDateString('fr-FR', opts)}
                     </div>`;
        }
        preview.innerHTML = html;
        preview.style.display = 'flex';
    } else {
        preview.style.display = 'none';
    }

    // Validation capacité
    const errBox = document.getElementById('err-capacite');
    const btn    = document.getElementById('btn-planif');

    if (nbJours > 0 && capacite < NB_SOUTENANCES_TOTAL) {
        document.getElementById('err-msg').innerHTML =
            `Impossible : ${NB_SOUTENANCES_TOTAL} soutenances pour seulement
             ${capacite} créneaux disponibles (${nbJours}j × ${NB_SALLES} salles × ${NB_SEANCES_PAR_JOUR} séances).
             <br>Choisissez au moins <strong>${minJours} jour${minJours>1?'s':''}</strong>.`;
        errBox.style.display = 'flex';
        btn.disabled = true;
        btn.style.opacity = '0.5';
    } else {
        errBox.style.display = 'none';
        btn.disabled = false;
        btn.style.opacity = '1';
    }
}

recalcul();
</script>