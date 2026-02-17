/**
 * Cube 3D split — rotation séquentielle 4 faces
 *
 * Architecture :
 * - Angle CUMULATIF (toujours ±90° par clic) → pas de saut de 270° au cycle
 * - offsetHeight pour la profondeur (pas getBoundingClientRect qui bouge en 3D)
 * - Aucun `filter` sur les cubes (ça casse preserve-3d)
 * - Stagger géré en CSS (transition-delay sur le cube droit)
 */
document.addEventListener('DOMContentLoaded', function () {
  var btnDown = document.getElementById('demo-cube-btn');
  var btnUp = document.getElementById('demo-cube-btn-back');
  var container = document.querySelector('.presentation__content');
  var cubeLeft = container ? container.querySelector('.cube--left') : null;
  var cubeRight = container ? container.querySelector('.cube--right') : null;
  var dots = container ? container.querySelectorAll('.presentation__dot') : null;

  if (!btnDown || !btnUp || !cubeLeft || !cubeRight || !container) return;

  var TOTAL_FACES = 4;
  var STEP_ANGLE = 90;
  var TRANSITION_MS = 1100; // 900ms transition + 150ms stagger + marge

  var currentStep = 0;      // face visible (0-3)
  var cumulativeAngle = 0;  // angle total cumulé (croît indéfiniment)
  var isAnimating = false;
  var hintRemoved = false;

  // ─── Profondeur 3D dynamique ───

  /**
   * Calcule --cube-half-h depuis la hauteur LAYOUT du container.
   * Utilise offsetHeight (insensible aux transforms 3D) et non
   * getBoundingClientRect (qui retourne la projection 2D = taille visuelle).
   */
  function updateCubeDepth() {
    if (isAnimating) return; // pas de recalcul pendant l'animation
    var h = container.offsetHeight;
    // Compenser le zoom perspectif : la face frontale est grossie par
    // le ratio perspective / (perspective - translateZ). On réduit halfH
    // pour que la taille VISUELLE de la face = taille du container.
    // Avec perspective=2000px : scale = 2000 / (2000 - halfH)
    // On veut scale * h_visual = h → halfH = h/2 ajusté.
    // Formule : halfH = perspective - sqrt(perspective² - perspective * h)
    // Simplification : halfH ≈ h / 2 * (1 - h / (4 * perspective))
    var perspective = 2000;
    var halfH = Math.round((h / 2) * (1 - h / (4 * perspective)));
    if (halfH > 0) {
      container.style.setProperty('--cube-half-h', halfH + 'px');
    }
  }

  updateCubeDepth();

  // ResizeObserver avec debounce
  if (typeof ResizeObserver !== 'undefined') {
    var resizeTimer;
    var ro = new ResizeObserver(function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(updateCubeDepth, 150);
    });
    ro.observe(container);
  }

  // ─── Bounce hint (flèche ↓) ───

  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    btnDown.classList.add('arrow-btn--hint');
  }

  function removeHint() {
    if (hintRemoved) return;
    hintRemoved = true;
    btnDown.classList.remove('arrow-btn--hint');
  }

  // ─── Navigation ───

  /**
   * Tourne le cube d'un nombre de steps (positif = suivant, négatif = précédent).
   * L'angle est cumulatif : on ajoute/soustrait toujours un multiple de 90°.
   * Pas de saut brutal au cycle (face 4→1 = -90° comme les autres).
   *
   * @param {number} steps — nombre de faces à parcourir (+1, -1, ou ±2 pour les dots)
   */
  function rotate(steps) {
    if (isAnimating || steps === 0) return;

    removeHint();
    isAnimating = true;

    // Angle cumulatif : cube gauche tourne vers le bas (négatif)
    cumulativeAngle -= steps * STEP_ANGLE;

    // Quelle face est maintenant visible (modulo positif)
    currentStep = ((currentStep + steps) % TOTAL_FACES + TOTAL_FACES) % TOTAL_FACES;

    // Appliquer les transforms
    // Gauche : rotateX(cumulativeAngle) — tourne vers le bas pour "next"
    // Droite : rotateX(-cumulativeAngle) — sens opposé
    cubeLeft.style.transform = 'rotateX(' + cumulativeAngle + 'deg)';
    cubeRight.style.transform = 'rotateX(' + (-cumulativeAngle) + 'deg)';

    // Ombre dynamique sur le container (pas sur les cubes — filter casse preserve-3d)
    container.classList.add('is-rotating');

    updateDots(currentStep);
    updateMobileFace(currentStep);

    // Fin de l'animation
    setTimeout(function () {
      container.classList.remove('is-rotating');
      isAnimating = false;
    }, TRANSITION_MS);
  }

  /**
   * Navigue vers une face spécifique (pour les dots).
   * Prend le chemin le plus court (max 2 steps).
   */
  function goToFace(targetIndex) {
    var diff = targetIndex - currentStep;
    // Chemin le plus court : normaliser entre -2 et +2
    if (diff > 2) diff -= TOTAL_FACES;
    if (diff < -2) diff += TOTAL_FACES;
    rotate(diff);
  }

  // ─── Dots indicateurs ───

  function updateDots(activeIndex) {
    if (!dots || dots.length === 0) return;
    dots.forEach(function (dot, i) {
      dot.classList.toggle('is-active', i === activeIndex);
    });
  }

  // ─── Mobile : afficher la bonne face (sans 3D) ───

  function updateMobileFace(step) {
    var facesLeft = cubeLeft.querySelectorAll('.cube__face');
    facesLeft.forEach(function (face, i) {
      face.classList.toggle('is-active-face', i === step);
    });
    cubeLeft.setAttribute('data-step', step);
  }

  // Init
  updateMobileFace(0);

  // ─── Event listeners ───

  btnDown.addEventListener('click', function () {
    rotate(1); // face suivante
  });

  btnUp.addEventListener('click', function () {
    rotate(-1); // face précédente
  });

  // Dots cliquables
  if (dots) {
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var idx = parseInt(dot.getAttribute('data-index'), 10);
        if (!isNaN(idx)) goToFace(idx);
      });
    });
  }

  // Accessibilité clavier
  container.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
      e.preventDefault();
      rotate(1);
    } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
      e.preventDefault();
      rotate(-1);
    }
  });
});
