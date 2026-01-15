// Animation Rubik's Cube 3D temporaire pour la démo

document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('demo-cube-btn');
  const cubes = document.querySelectorAll('.presentation__content .cube');
  let flipped = false;

  btn.addEventListener('click', function() {
    flipped = !flipped;
    cubes.forEach(cube => {
      cube.classList.toggle('is-flipped', flipped);
    });
    btn.textContent = flipped ? 'Revenir à la vue initiale' : 'Voir le Rubik\'s Cube';
  });
});
