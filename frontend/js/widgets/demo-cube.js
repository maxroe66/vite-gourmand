// Animation Rubik's Cube 3D temporaire pour la démo


// Animation Rubik's Cube 3D split (nouvelle version)

document.addEventListener('DOMContentLoaded', function() {
  const btnDown = document.getElementById('demo-cube-btn');
  const btnUp = document.getElementById('demo-cube-btn-back');
  const cubes = document.querySelectorAll('.presentation__content .cube--split');
  let flipped = false;

  btnDown.addEventListener('click', function() {
    if (flipped) return;
    flipped = true;
    cubes.forEach(cube => cube.classList.add('is-flipped'));
    btnDown.classList.add('is-hidden');
    btnUp.classList.add('is-active');
  });

  btnUp.addEventListener('click', function() {
    if (!flipped) return;
    flipped = false;
    cubes.forEach(cube => cube.classList.remove('is-flipped'));
    btnUp.classList.remove('is-active');
    btnUp.classList.add('is-hidden');
    btnDown.classList.remove('is-hidden');
    btnDown.classList.add('is-active');
  });
});
