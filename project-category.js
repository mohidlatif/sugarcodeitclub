(function () {
  var list = document.querySelector('[data-project-category]');
  if (!list) {
    return;
  }

  var category = list.getAttribute('data-project-category');

  fetch('/api/projects.php?category=' + encodeURIComponent(category), { cache: 'no-store' })
    .then(function (response) {
      if (!response.ok) {
        throw new Error('Could not load projects');
      }
      return response.json();
    })
    .then(function (projects) {
      list.innerHTML = '';

      if (!projects.length) {
        list.innerHTML = '<div class="empty-state"><h3>No projects added yet</h3><p>Projects in this category will appear here.</p></div>';
        return;
      }

      projects.forEach(function (project) {
        var card = document.createElement('article');
        card.className = 'card';

        var meta = document.createElement('div');
        meta.className = 'card-meta';
        meta.textContent = project.status || 'Club project';
        card.appendChild(meta);

        var title = document.createElement('h3');
        title.textContent = project.title || 'Project';
        card.appendChild(title);

        if (project.description) {
          var description = document.createElement('p');
          description.textContent = project.description;
          card.appendChild(description);
        }

        if (project.link) {
          var link = document.createElement('a');
          link.className = 'small-button';
          link.href = project.link;
          link.target = '_blank';
          link.rel = 'noopener';
          link.textContent = 'Project Link';
          card.appendChild(link);
        }

        list.appendChild(card);
      });
    })
    .catch(function () {
      list.innerHTML = '<div class="empty-state"><h3>Projects could not be loaded</h3><p>Please try again later.</p></div>';
    });
}());
